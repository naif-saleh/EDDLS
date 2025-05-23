<?php

namespace App\Services;

use App\Models\License;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    /**
     * Cache TTL in minutes
     */
    const CACHE_TTL = 60;

    /**
     * Warning threshold for license expiration (in days)
     */
    const EXPIRATION_WARNING_DAYS = 14;

    /**
     * Get the active license for a tenant
     *
     * @param  int|null  $tenantId
     * @return License|null
     */
    public function getActiveLicense($tenantId = null, $skipCache = false)
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $cacheKey = "tenant_{$tenantId}_active_license";

        // Skip cache if requested (for write operations)
        if ($skipCache) {
            return License::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->orderBy('valid_until', 'desc')
                ->first();
        }

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL), function () use ($tenantId) {
            return License::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where('valid_until', '>=', now())
                ->orderBy('valid_until', 'desc')
                ->first();
        });
    }

     /**
     * Check validProvidersCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function validProvidersCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_providers <= 0) {
            Log::info('License max providers limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_providers--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max providers limit decreased to '.$license->max_providers);

        return true;
    }


    /**
     * Check IcrementProvidersCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function icrementProvidersCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }



        // Modify and save the license
        $license->max_providers++;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);


        return true;
    }


    /**
     * Check Increment Agent Count
     *
     * @param  int|null  $tenantId
     * @return bool
     */

    public function incrementAgentsCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }



        // Modify and save the license
        $license->max_agents++;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        return true;
    }


     /**
     * Check validAgentsCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */

    public function validAgentsCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_agents <= 0) {
            Log::info('License max agents limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_agents--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max providers limit decreased to '.$license->max_agents);

        return true;
    }


    /**
     * Check validCampaignsCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function validCampaignsCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_campaigns <= 0) {
            Log::info('License max campaigns limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_campaigns--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max campaigns limit decreased to '.$license->max_campaigns);

        return true;
    }


    /**
     * Check validCampaignsCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function validDialCallsCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_dial_calls <= 0) {
            Log::info('License max campaigns limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_dial_calls--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max Dial Calls limit decreased to '.$license->max_dial_calls);

        return true;
    }


    /**
     * Check validDistCallsCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function validDistCallsCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_dist_calls <= 0) {
            Log::info('License max Distributor calls limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_dist_calls--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max Dial Calls limit decreased to '.$license->max_dial_calls);

        return true;
    }


     /**
     * Check validContactsPerCampaignCount
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function validContactsPerCampaignCount($tenantId = null)
    {
        // Get fresh license without cache for modification
        $license = $this->getActiveLicense($tenantId, true);

        if (! $license) {
            return false;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return false;
        }

        if ($license->max_contacts_per_campaign <= 0) {
            Log::info('License max contacts limit reached');

            return false;
        }

        // Modify and save the license
        $license->max_contacts_per_campaign--;
        $license->save();

        // Clear the cache to ensure subsequent reads get the updated value
        $cacheKey = "tenant_{$tenantId}_active_license";
        Cache::forget($cacheKey);

        Log::info('License max contacts limit decreased to '.$license->max_contacts_per_campaign);

        return true;
    }



    /**
     * Check if a tenant has a valid license
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function hasValidLicense($tenantId = null)
    {
        return $this->getActiveLicense($tenantId) !== null;
    }

    /**
     * Check if tenant has reached the campaign limit
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function hasReachedCampaignLimit($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return true; // No license means limit is reached
        }

        return $license->hasReachedCampaignLimit();
    }

    /**
     * Check if tenant has reached the agent limit
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function hasReachedAgentLimit($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return true; // No license means limit is reached
        }

        return $license->hasReachedAgentLimit();
    }

    /**
     * Check if tenant has reached the provider limit
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function hasReachedProviderLimit($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return true; // No license means limit is reached
        }

        return $license->hasReachedProviderLimit();
    }

    /**
     * Get remaining quota for a specific resource
     *
     * @param  string  $resource
     * @param  int|null  $tenantId
     * @return int|null
     */
    public function getRemainingQuota($resource, $tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return 0;
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return null;
        }

        switch ($resource) {
            case 'campaigns':
                return max(0, $license->max_campaigns - $tenant->campaigns()->count());
            case 'agents':
                return max(0, $license->max_agents - $tenant->agents()->count());
            case 'providers':
                return max(0, $license->max_providers - $tenant->providers()->count());
            case 'dist_calls':
                return max(0, $license->max_dist_calls - $tenant->distributorCalls()->count());
            case 'dial_calls':
                return max(0, $license->max_dial_calls - $tenant->dialerCalls()->count());
            default:
                return null;
        }
    }

    /**
     * Check if license is expiring soon
     *
     * @param  int|null  $tenantId
     * @return bool
     */
    public function isExpiringSoon($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return false;
        }

        $daysRemaining = $license->getDaysRemaining();

        return $daysRemaining >= 0 && $daysRemaining <= self::EXPIRATION_WARNING_DAYS;
    }

    /**
     * Get expiration status information
     *
     * @param  int|null  $tenantId
     * @return array
     */
    public function getExpirationStatus($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return [
                'has_license' => false,
                'is_valid' => false,
                'days_remaining' => 0,
                'expiring_soon' => false,
                'expired' => true,
            ];
        }

        $daysRemaining = $license->getDaysRemaining();

        return [
            'has_license' => true,
            'is_valid' => $license->isValid(),
            'days_remaining' => max(0, $daysRemaining),
            'expiring_soon' => $daysRemaining >= 0 && $daysRemaining <= self::EXPIRATION_WARNING_DAYS,
            'expired' => $daysRemaining < 0,
            'valid_until' => $license->valid_until->format('Y-m-d'),
        ];
    }

    /**
     * Get license usage statistics
     *
     * @param  int|null  $tenantId
     * @return array
     */
    public function getLicenseUsage($tenantId = null)
    {
        $license = $this->getActiveLicense($tenantId);

        if (! $license) {
            return [
                'has_license' => false,
                'usage' => [],
            ];
        }

        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return [
                'has_license' => true,
                'usage' => [],
            ];
        }

        $campaignCount = $tenant->campaigns()->count();
        $agentCount = $tenant->agents()->count();
        $providerCount = $tenant->providers()->count();
        $distCallCount = $tenant->distributorCalls()->count();
        $dialCallCount = $tenant->dialerCalls()->count();

        return [
            'has_license' => true,
            'usage' => [
                'campaigns' => [
                    'used' => $campaignCount,
                    'limit' => $license->max_campaigns,
                    'percentage' => $this->calculatePercentage($campaignCount, $license->max_campaigns),
                ],
                'agents' => [
                    'used' => $agentCount,
                    'limit' => $license->max_agents,
                    'percentage' => $this->calculatePercentage($agentCount, $license->max_agents),
                ],
                'providers' => [
                    'used' => $providerCount,
                    'limit' => $license->max_providers,
                    'percentage' => $this->calculatePercentage($providerCount, $license->max_providers),
                ],
                'dist_calls' => [
                    'used' => $distCallCount,
                    'limit' => $license->max_dist_calls,
                    'percentage' => $this->calculatePercentage($distCallCount, $license->max_dist_calls),
                ],
                'dial_calls' => [
                    'used' => $dialCallCount,
                    'limit' => $license->max_dial_calls,
                    'percentage' => $this->calculatePercentage($dialCallCount, $license->max_dial_calls),
                ],
            ],
        ];
    }

    /**
     * Calculate percentage usage
     *
     * @param  int  $used
     * @param  int  $limit
     * @return float
     */
    protected function calculatePercentage($used, $limit)
    {
        if ($limit <= 0) {
            return 100;
        }

        return min(100, round(($used / $limit) * 100, 1));
    }

    /**
     * Validate a license key
     *
     * @param  string  $licenseKey
     * @param  int|null  $tenantId
     * @return array
     */
    public function validateLicenseKey($licenseKey, $tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;

        // Basic validation - check if license exists
        $license = License::where('license_key', $licenseKey)
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $license) {
            Log::warning("Invalid license key attempted: {$licenseKey} for tenant {$tenantId}");

            return [
                'valid' => false,
                'message' => 'License key not found',
            ];
        }

        // Check if license date is valid
        if (! Carbon::now()->between($license->valid_from, $license->valid_until)) {
            return [
                'valid' => false,
                'message' => 'License key has expired or is not yet valid',
                'valid_from' => $license->valid_from->format('Y-m-d'),
                'valid_until' => $license->valid_until->format('Y-m-d'),
            ];
        }

        // Check if license is active
        if (! $license->is_active) {
            return [
                'valid' => false,
                'message' => 'License key is inactive',
            ];
        }

        return [
            'valid' => true,
            'message' => 'License key is valid',
            'license' => $license,
        ];
    }

    /**
     * Activate a license for a tenant
     *
     * @param  string  $licenseKey
     * @param  int|null  $tenantId
     * @return array
     */
    public function activateLicense($licenseKey, $tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;

        // First, validate the license key
        $validation = $this->validateLicenseKey($licenseKey, $tenantId);

        if (! $validation['valid']) {
            return $validation;
        }

        $license = $validation['license'];

        // Deactivate any existing licenses for this tenant
        License::where('tenant_id', $tenantId)
            ->where('id', '!=', $license->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Activate this license if it's not already active
        if (! $license->is_active) {
            $license->is_active = true;
            $license->save();
        }

        // Clear cache for this tenant
        $this->clearLicenseCache($tenantId);

        Log::info("License {$licenseKey} activated for tenant {$tenantId}");

        return [
            'valid' => true,
            'message' => 'License activated successfully',
            'license' => $license,
        ];
    }

    /**
     * Clear license cache for a tenant
     *
     * @param  int|null  $tenantId
     * @return void
     */
    public function clearLicenseCache($tenantId = null)
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;
        Cache::forget("tenant_{$tenantId}_active_license");
    }

    /**
     * Create a new license for a tenant
     *
     * @return License
     */
    public function createLicense(array $data)
    {
        $license = License::create($data);

        // Clear cache for this tenant
        $this->clearLicenseCache($license->tenant_id);

        Log::info("New license created for tenant {$license->tenant_id}: {$license->license_key}");

        return $license;
    }

    /**
     * Update an existing license
     *
     * @param  int  $licenseId
     * @return License|null
     */
    public function updateLicense($licenseId, array $data)
    {
        $license = License::find($licenseId);

        if (! $license) {
            return null;
        }

        $license->update($data);

        // Clear cache for this tenant
        $this->clearLicenseCache($license->tenant_id);

        Log::info("License {$license->license_key} updated for tenant {$license->tenant_id}");

        return $license;
    }

    /**
     * Deactivate a license
     *
     * @param  int  $licenseId
     * @return License|null
     */
    public function deactivateLicense($licenseId)
    {
        $license = License::find($licenseId);

        if (! $license) {
            return null;
        }

        $license->is_active = false;
        $license->save();

        // Clear cache for this tenant
        $this->clearLicenseCache($license->tenant_id);

        Log::info("License {$license->license_key} deactivated for tenant {$license->tenant_id}");

        return $license;
    }

    /**
     * Generate a unique license key
     *
     * @return string
     */
    public function generateLicenseKey()
    {
        $prefix = 'TLCX-';
        $segments = [
            strtoupper(substr(md5(microtime()), 0, 5)),
            strtoupper(substr(md5(rand()), 0, 5)),
            strtoupper(substr(md5(uniqid()), 0, 5)),
            strtoupper(substr(md5(time()), 0, 5)),
        ];

        $licenseKey = $prefix.implode('-', $segments);

        // Make sure this key doesn't already exist
        while (License::where('license_key', $licenseKey)->exists()) {
            $segments = [
                strtoupper(substr(md5(microtime()), 0, 5)),
                strtoupper(substr(md5(rand()), 0, 5)),
                strtoupper(substr(md5(uniqid()), 0, 5)),
                strtoupper(substr(md5(time()), 0, 5)),
            ];

            $licenseKey = $prefix.implode('-', $segments);
        }

        return $licenseKey;
    }


    public static function forTenant($tenantId)
    {
        return DB::connection('tenant')
            ->table('licenses')
            ->where('tenant_id', $tenantId)
            ->first();
    }
}
