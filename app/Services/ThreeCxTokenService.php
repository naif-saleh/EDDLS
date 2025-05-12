<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\ApiIntegration;

/**
 * Class ThreeCxTokenService
 *
 * This service handles the generation and caching of the 3CX API token.
 * It uses Laravel's Cache and HTTP client to manage the token lifecycle.
 * It fetches API credentials from the database per tenant.
 */
class ThreeCxTokenService
{
    protected $authUrl;
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? auth()->user()->tenant_id;
        $this->loadCredentials();
    }

    /**
     * Load API credentials from the database for the current tenant
     */
    protected function loadCredentials()
    {
        $integration = ApiIntegration::where('tenant_id', $this->tenantId)->first();

        if (!$integration) {
            Log::error('❌ No API integration found for tenant ID: ' . $this->tenantId);
            return;
        }

        $this->authUrl = $integration->pbx_url . '/connect/token';
        $this->clientId = $integration->client_id;
        $this->clientSecret = $integration->client_secret;
    }

    /**
     * Get the cached token or generate a new one if expired.
     */
    public function getToken()
    {
        $cacheKey = 'three_cx_token_' . $this->tenantId;

        return Cache::remember($cacheKey, now()->addMinutes(55), function () {
            return $this->generateToken();
        });
    }

    /**
     * Generate a new token and cache it.
     */
    protected function generateToken()
    {
        Log::info('🔑 Generating new token for 3CX API for tenant: ' . $this->tenantId);

        if (!$this->clientId || !$this->clientSecret || !$this->authUrl) {
            Log::error('❌ Missing API credentials for tenant: ' . $this->tenantId);
            return null;
        }

        try {
            $response = Http::asForm()->post($this->authUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if ($response->successful() && isset($response['access_token'])) {
                $token = $response['access_token'];
                $expiresIn = max(($response['expires_in'] ?? 3600) - 60, 300); // Ensure at least 5 min cache

                $cacheKey = 'three_cx_token_' . $this->tenantId;
                Cache::put($cacheKey, $token, now()->addSeconds($expiresIn));
                Log::info('✅ Token generated and cached successfully for tenant: ' . $this->tenantId);

                return $token;
            }

            Log::error('❌ Failed to generate token for tenant ' . $this->tenantId . ': ' . $response->body());
        } catch (\Exception $e) {
            Log::error('🚨 Token generation error for tenant ' . $this->tenantId . ': ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Forcefully refresh the token and update the cache.
     */
    public function refreshToken()
    {
        Log::warning('🔄 Forcing token refresh due to 401 Unauthorized for tenant: ' . $this->tenantId);

        $lockName = 'three_cx_token_lock_' . $this->tenantId;
        $cacheKey = 'three_cx_token_' . $this->tenantId;

        return Cache::lock($lockName, 5)->block(5, function () use ($cacheKey) {
            $token = $this->generateToken();
            Cache::put($cacheKey, $token, now()->addMinutes(55));
            return $token;
        });
    }
}
