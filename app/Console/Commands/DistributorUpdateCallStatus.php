<?php

namespace App\Console\Commands;

use App\Jobs\DistributorUpdateCallStatusJob;
use App\Models\Agent;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\ThreeCXIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DistributorUpdateCallStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:distributor-update-call-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant ID: {$tenant->id}");
            $agents = Agent::where('status', true)->where('tenant_id', $tenant->id)->get();
            foreach ($agents as $agent) {
                try {
                    // Initialize the 3CX service for this tenant
                    $threeCxService = new ThreeCXIntegrationService($tenant->id);
                    $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($agent->extension);

                    if (! isset($refreshCallsResponse['value']) || empty($refreshCallsResponse['value'])) {
                        $this->info("No active calls found for agent {$agent->extension} on tenant {$tenant->id}");
                        continue;
                    }

                    $callCount = count($refreshCallsResponse['value']);
                    $this->info("Processing {$agent->extension}: Found {$callCount} active calls");

                    // Dispatch jobs for each call update
                    foreach ($refreshCallsResponse['value'] as $activeCall) {
                        $callId = $activeCall['Id'] ?? null;
                        $callStatus = $activeCall['Status'] ?? null;

                        if (! $callId || ! $callStatus) {
                            continue;
                        }

                        // Dispatch job with all necessary information
                        DistributorUpdateCallStatusJob::dispatch(
                            $tenant->id,
                            $callId, 
                            $callStatus, 
                            $refreshCallsResponse['value']
                        );
                        
                        $jobsDispatched++;
                    }

                } catch (\Exception $e) {
                    $this->error("Error processing calls for agent {$agent->extension}: {$e->getMessage()}");
                    Log::error("Error in DialerUpdateCallStatus command: {$e->getMessage()}", [
                        'tenant_id' => $tenant->id,
                        'agent_extension' => $agent->extension,
                        'exception' => $e,
                    ]);
                }
            }
        }

        $this->info("Dispatched {$jobsDispatched} call status update jobs in total");
    }
}
