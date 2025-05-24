<?php

namespace App\Services;

use App\Models\Agent;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ApiIntegration;
use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Tenant;

class ThreeCXIntegrationService
{
    protected $client;
    protected $tokenService;
    protected $apiUrl;
    protected $tenantId;
    protected $tenant;

    public function __construct($tenant = null, $tokenService = null)
    {
        // Handle both tenant object and tenant ID
        if ($tenant instanceof Tenant) {
            $this->tenant = $tenant;
            $this->tenantId = $tenant->id;
        } elseif (is_numeric($tenant)) {
            $this->tenantId = $tenant;
            $this->tenant = Tenant::find($tenant);
        } else {
            $this->tenantId = auth()->user()->tenant_id ?? null;
            $this->tenant = $this->tenantId ? Tenant::find($this->tenantId) : null;
        }

        if (!$this->tenant) {
            throw new \Exception('No valid tenant found for ThreeCXIntegrationService');
        }

        $this->tokenService = $tokenService ?? new ThreeCxTokenService($this->tenantId);
        $this->client = new Client();
        $this->loadApiUrl();
    }

    /**
     * Load API URL from the database for the current tenant
     */
    protected function loadApiUrl()
    {
        if (!$this->tenant) {
            Log::error('❌ No tenant found for loading API URL');
            return;
        }

        TenantService::setConnection($this->tenant);
        $integration = ApiIntegration::on('tenant')->where('tenant_id', $this->tenantId)->first();

        if (!$integration) {
            Log::error('❌ No API integration found for tenant ID: ' . $this->tenantId);
            return;
        }

        $this->apiUrl = $integration->pbx_url;
    }

    /**
     * Get a fresh token for API requests
     */
    public function getToken()
    {
        try {
            $token = $this->tokenService->getToken();
            if (!$token) {
                throw new \Exception('Failed to retrieve a valid token for tenant: ' . $this->tenantId);
            }

            return $token;
        } catch (\Exception $e) {
            Log::error('❌ Failed to retrieve token for tenant ' . $this->tenantId . ': ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get all users from 3cx API
     * @return array
     */
    public function getUsersFromThreeCxApi()
    {
        if (!$this->apiUrl) {
            Log::error('❌ Missing API URL for tenant: ' . $this->tenantId);
            return [];
        }

        $retries = 0;
        $maxRetries = 1;

        while ($retries <= $maxRetries) {
            try {
                $token = $this->getToken();
                $url = $this->apiUrl . '/xapi/v1/Users';

                $response = $this->client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ],
                    'timeout' => 30,
                ]);

                if ($response->getStatusCode() === 200) {
                    Log::info('✅ Successfully fetched users from 3CX API for tenant: ' . $this->tenantId);
                    return json_decode($response->getBody()->getContents(), true);
                }

                throw new \Exception('Failed to fetch users for tenant ' . $this->tenantId . '. HTTP Status: ' . $response->getStatusCode());
            } catch (\Exception $e) {
                if ($retries < $maxRetries && strpos($e->getMessage(), '401') !== false) {
                    Log::warning("🔄 401 Unauthorized detected for tenant {$this->tenantId}, refreshing token...");

                    $this->tokenService->refreshToken();
                    $retries++;

                    continue;
                }

                Log::error("❌ Error fetching users from 3CX API for tenant {$this->tenantId}: " . $e->getMessage());
                return [];
            }
        }

        return [];
    }

    /**
     * Get all active calls for a provider
     */
    public function getActiveCallsForProvider($providerExtension)
    {
        if (!$this->apiUrl) {
            Log::error('❌ Missing API URL for tenant: ' . $this->tenantId);
            return [];
        }

        $retries = 0;
        $maxRetries = 1;

        while ($retries <= $maxRetries) {
            try {
                $token = $this->getToken();
                $filter = "contains(Caller, '{$providerExtension}')";
                $url = $this->apiUrl . '/xapi/v1/ActiveCalls?$filter=' . urlencode($filter);

                $response = $this->client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 15, // Reduced timeout to avoid blocking
                ]);

                if ($response->getStatusCode() === 200) {
                    return json_decode($response->getBody()->getContents(), true);
                }

                throw new \Exception('Failed to fetch active calls for tenant ' . $this->tenantId . '. HTTP Status: ' . $response->getStatusCode());
            } catch (\Exception $e) {
                if ($retries < $maxRetries && strpos($e->getMessage(), '401') !== false) {
                    Log::warning("🔄 401 Unauthorized detected for tenant {$this->tenantId}, refreshing token...");

                    // Refresh token only once
                    $this->tokenService->refreshToken();
                    $retries++;

                    continue;
                }

                Log::error("❌ Error fetching active calls for provider {$providerExtension} for tenant {$this->tenantId}: " . $e->getMessage());
                return [];
            }
        }

        return [];
    }

    /**
     * Get all active calls
     */
    public function getAllActiveCalls()
    {
        if (!$this->apiUrl) {
            Log::error('❌ Missing API URL for tenant: ' . $this->tenantId);
            return [];
        }

        $retries = 0;
        $maxRetries = 3; // Increase max retries
        $retryDelay = 2; // Delay between retries

        while ($retries <= $maxRetries) {
            try {
                // Get a fresh token on each attempt
                $token = $this->getToken();
                $url = $this->apiUrl . '/xapi/v1/ActiveCalls';

                $response = $this->client->get($url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $token,
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 30,
                ]);

                return json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                if ($retries < $maxRetries && strpos($e->getMessage(), 'cURL error 28') !== false) {
                    Log::warning("⏳ Retry attempt {$retries} due to timeout (cURL error 28) for tenant {$this->tenantId}");

                    sleep($retryDelay);
                    $retries++;
                    continue;
                }

                // Handle 401 token refresh
                if ($retries < $maxRetries && strpos($e->getMessage(), '401') !== false) {
                    // If we get a 401, force token refresh and retry
                    $this->tokenService->refreshToken();
                    $retries++;
                    Log::info("Token refresh attempt {$retries} after 401 error for tenant {$this->tenantId}");
                    continue;
                }

                // Log the error and throw it after retries
                Log::error('❌ Failed to fetch active calls for tenant ' . $this->tenantId . ': ' . $e->getMessage());
                throw $e;
            }
        }

        return [];
    }

    /**
     * Make a call using 3CX API with improved error handling and caching
     *
     * @param  string  $providerExtension
     * @param  string  $destination
     * @return array
     *
     * @throws \Exception
     */
    public function makeCall($providerExtension, $destination)
    {
        if (!$this->apiUrl) {
            Log::error('❌ Missing API URL for tenant: ' . $this->tenantId);
            throw new \Exception("API URL not configured for tenant: {$this->tenantId}");
        }

        try {
            $token = $this->getToken();
            $url = $this->apiUrl . "/callcontrol/{$providerExtension}/makecall";

            $response = $this->client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => ['destination' => $destination],
                'timeout' => 10,
            ]);
            Log::info("📞 Call made to {$destination} by {$providerExtension} for tenant {$this->tenantId}");

            $responseData = json_decode($response->getBody()->getContents(), true);
            if (!isset($responseData['result']['callid'])) {
                throw new \Exception("Missing call ID in response for tenant {$this->tenantId}");
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error("❌ Make call failed for tenant {$this->tenantId}: " . $e->getMessage());
            throw $e;
        }
    }


     public function isAgentInCall(Agent $agent)
    {
        // Check if the agent is already in a call
        $token = $this->getToken();
        $url = $this->apiUrl . "/callcontrol/{$agent->extension}/participants";
        $response = $this->client->get($url, [
            'headers' => ['Authorization' => "Bearer $token"],
            'timeout' => 10,
        ]);
        Log::info('Response from isAgentInCall:', ['response' => $response->getBody()->getContents()]);
        $participants = json_decode($response->getBody(), true);
        return collect($participants)->contains(fn($p) => in_array($p['status'], ['Connected', 'Dialing', 'Ringing']));
    }


    public function makeCallDist(Agent $agent, $destination)
    {
        // Make a call to the given destination
        $token = $this->getToken();
        $mobileDevice = $this->getDeviceForAgent($agent);
        if (!$mobileDevice) {
            throw new \Exception("No 3CX Mobile Client device found for agent {$agent->extension}");
        }
        $url = $this->apiUrl . "/callcontrol/{$agent->extension}/devices/{$mobileDevice['device_id']}/makecall";
        $response = $this->client->post($url, [
            'headers' => ['Authorization' => "Bearer $token"],
            'json' => ['destination' => $destination],
            'timeout' => 10,
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getDeviceForAgent(Agent $agent)
    {
        // Fetch devices for agent and return the mobile device
        $token = $this->getToken();
        $url = $this->apiUrl . "/callcontrol/{$agent->extension}/devices";
        $response = $this->client->get($url, [
            'headers' => ['Authorization' => "Bearer $token"],
            'timeout' => 10,
        ]);
        $devices = json_decode($response->getBody(), true);

        return collect($devices)->firstWhere('user_agent', '3CX Mobile Client');
    }

/**
 * Update call record in database with consistent format
 * 
 * @param string $callId The ID of the call to update
 * @param string $status The current status of the call
 * @param array $callsData The array containing call data
 * @return mixed
 */
public function updateCallRecord($callId, $status, $callsData)
{
    $talking_duration = null;
    $dial_duration = null;
    $currentDuration = null;
    
    // Find the specific call data in the array
    $callData = null;
    foreach ($callsData as $call) {
        if (isset($call['Id']) && $call['Id'] == $callId) {
            $callData = $call;
            break;
        }
    }

    // Calculate durations if call data is found
    if ($callData && isset($callData['EstablishedAt']) && isset($callData['ServerNow'])) {
        $establishedAt = Carbon::parse($callData['EstablishedAt']);
        $serverNow = Carbon::parse($callData['ServerNow']);
        $currentDuration = $establishedAt->diff($serverNow)->format('%H:%I:%S');
        
        // Retrieve existing record to preserve any existing durations
        $existingRecord = CallLog::where('call_id', $callId)->first();

        if ($existingRecord) {
            // Update durations based on current status
            switch ($status) {
                case 'Talking':
                    $talking_duration = $currentDuration;
                    $dial_duration = $existingRecord->dial_duration;
                    break;
                case 'Routing':
                    $dial_duration = $currentDuration;
                    $talking_duration = $existingRecord->talking_duration;
                    break;
                default:
                    $talking_duration = $existingRecord->talking_duration;
                    $dial_duration = $existingRecord->dial_duration;
            }
        }
    } else {
        // If updating without call data, preserve existing durations
        $existingRecord = CallLog::where('call_id', $callId)->first();

        if ($existingRecord) {
            $talking_duration = $existingRecord->talking_duration ?? null;
            $dial_duration = $existingRecord->dial_duration ?? null;
        }
    }

    return DB::transaction(function () use ($callId, $status, $talking_duration, $dial_duration, $currentDuration) {
        $report = CallLog::where('call_id', $callId)->update([
            'call_status' => $status,
            'talking_duration' => $talking_duration,
            'dial_duration' => $dial_duration,
        ]);

        Contact::where('call_id', $callId)->update(['status' => $status]);

        Log::info("ADialParticipantsCommand ☎️✅ Call status updated for call_id: {$callId}, " .
            "Status: {$status}, " .
            "Duration: " . ($currentDuration ?? 'N/A'));

        return $report;
    });
}

}