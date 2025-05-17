<?php

namespace App\Console\Commands;

use App\Jobs\DialerUpdateCallStatusJob;
use App\Jobs\UpdateCallStatusJob;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\ThreeCXIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

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
        $providers = Provider::where('status', true)->get();
        $jobsDispatched = 0;
        
        $tenants = Tenant::where('status', 'active')
            ->whereHas('licenses', function ($query) {
                $query->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now());
            })
            ->get();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant ID: {$tenant->id}");
            
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
                    }

                } catch (\Exception $e) {
                    $this->error("Error processing calls for provider {$provider->extension}: {$e->getMessage()}");
                    Log::error("Error in DialerUpdateCallStatus command: {$e->getMessage()}", [
                        'tenant_id' => $tenant->id,
                        'provider_extension' => $provider->extension,
                        'exception' => $e,
                    ]);
                }
            }
        }

        $this->info("Dispatched {$jobsDispatched} call status update jobs in total");
    }
}