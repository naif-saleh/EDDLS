<?php

namespace App\Console\Commands;

use App\Jobs\DialerUpdateCallStatusJob;
use App\Jobs\UpdateCallStatusJob;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\ThreeCXIntegrationService;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DialerUpdateCallStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:dialer-update-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update statuses for all active calls using queue jobs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobsDispatched = 0;
        
        $tenants = Tenant::where('status', 'active')
            ->whereHas('licenses', function ($query) {
                $query->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now());
            })
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No active tenants found');
            return;
        }

        $this->info("Processing {$tenants->count()} active tenants");

        foreach ($tenants as $tenant) {
            try {
                $this->info("Processing tenant ID: {$tenant->id} - {$tenant->name}");
                
                // Set tenant connection and verify it's working
                TenantService::setConnection($tenant);
                
                // Test the connection by running a simple query
                DB::connection('tenant')->select('SELECT 1');
                $this->info("Tenant connection established successfully for {$tenant->name}");
                
                // Get providers for this specific tenant
                $providers = Provider::on('tenant')->where('status', true)->get();
                
                if ($providers->isEmpty()) {
                    $this->info("No active providers found for tenant {$tenant->name}");
                    continue;
                }

                $this->info("Found {$providers->count()} active providers for tenant {$tenant->name}");
                
                foreach ($providers as $provider) {
                    try {
                        // Initialize the 3CX service for this tenant
                        $threeCxService = new ThreeCXIntegrationService($tenant->id);
                        $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($provider->extension);

                        if (! isset($refreshCallsResponse['value']) || empty($refreshCallsResponse['value'])) {
                            $this->info("No active calls found for provider {$provider->extension} on tenant {$tenant->id}");
                            continue;
                        }

                        $callCount = count($refreshCallsResponse['value']);
                        $this->info("Processing {$provider->extension}: Found {$callCount} active calls");

                        // Dispatch jobs for each call update
                        foreach ($refreshCallsResponse['value'] as $activeCall) {
                            $callId = $activeCall['Id'] ?? null;
                            $callStatus = $activeCall['Status'] ?? null;

                            if (! $callId || ! $callStatus) {
                                $this->warn("Skipping call with missing ID or status");
                                continue;
                            }

                            // Dispatch job with all necessary information
                            DialerUpdateCallStatusJob::dispatch(
                                $tenant->id,
                                $callId, 
                                $callStatus, 
                                $refreshCallsResponse['value']
                            );
                            
                            $jobsDispatched++;
                            
                            $this->info("Dispatched job for call ID: {$callId} with status: {$callStatus}");
                        }

                    } catch (\Exception $e) {
                        $this->error("Error processing calls for provider {$provider->extension}: {$e->getMessage()}");
                        Log::error("Error processing provider in DialerUpdateCallStatus command: {$e->getMessage()}", [
                            'tenant_id' => $tenant->id,
                            'provider_id' => $provider->id,
                            'provider_extension' => $provider->extension,
                            'exception' => $e,
                        ]);
                        continue;
                    }
                }

            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error("Error processing tenant in DialerUpdateCallStatus command: {$e->getMessage()}", [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'exception' => $e,
                ]);
                continue;
            }
        }

        $this->info("Completed processing all tenants");
        $this->info("Dispatched {$jobsDispatched} call status update jobs in total");
    }
}