<?php

namespace App\Console\Commands;

use App\Jobs\DistributorUpdateCallStatusJob;
use App\Models\Agent;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\TenantService;
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
    protected $description = 'Update call status for distributor campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $jobsDispatched = 0;
        
        Log::info('Starting Distributor call status update process at '.now());
        
        $tenants = Tenant::where('status', 'active')
            ->whereHas('licenses', function ($query) {
                $query->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now());
            })
            ->get();

        if ($tenants->isEmpty()) {
            Log::info('No active tenants found for call status update');
            return;
        }

        Log::info('Processing call status updates for '.$tenants->count().' active tenants');

        foreach ($tenants as $tenant) {
            try {
                $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");
                
                // Set the tenant connection BEFORE querying agents
                TenantService::setConnection($tenant);
                
                // Now query agents using the tenant connection
                $agents = Agent::on('tenant')
                    ->where('status', 'active') // Changed from 'true' to 'active' to match your other command
                    ->where('tenant_id', $tenant->id)
                    ->get();

                if ($agents->isEmpty()) {
                    $this->info("No active agents found for tenant {$tenant->name}");
                    continue;
                }

                $this->info("Found {$agents->count()} active agents for tenant {$tenant->name}");

                foreach ($agents as $agent) {
                    try {
                        // Initialize the 3CX service for this tenant
                        $threeCxService = new ThreeCXIntegrationService($tenant);
                        $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($agent->extension);

                        if (!isset($refreshCallsResponse['value']) || empty($refreshCallsResponse['value'])) {
                            $this->info("No active calls found for agent {$agent->name} (Extension: {$agent->extension})");
                            continue;
                        }

                        $callCount = count($refreshCallsResponse['value']);
                        $this->info("Processing agent {$agent->name} (Extension: {$agent->extension}): Found {$callCount} active calls");

                        // Dispatch jobs for each call update
                        foreach ($refreshCallsResponse['value'] as $activeCall) {
                            $callId = $activeCall['Id'] ?? null;
                            $callStatus = $activeCall['Status'] ?? null;

                            if (!$callId || !$callStatus) {
                                Log::warning("Skipping call with missing ID or status", [
                                    'call_data' => $activeCall,
                                    'tenant_id' => $tenant->id,
                                    'agent_extension' => $agent->extension,
                                ]);
                                continue;
                            }

                            Log::info("Dispatching call status update job for Call ID: {$callId}, Status: {$callStatus}");

                            // Dispatch job with all necessary information
                            DistributorUpdateCallStatusJob::dispatch(
                                $tenant,
                                $callId, 
                                $callStatus, 
                                $refreshCallsResponse['value']
                            );
                            
                            $jobsDispatched++;
                        }

                    } catch (\Exception $e) {
                        $this->error("Error processing calls for agent {$agent->name} (Extension: {$agent->extension}): {$e->getMessage()}");
                        Log::error("Error processing agent calls in DistributorUpdateCallStatus command: {$e->getMessage()}", [
                            'tenant_id' => $tenant->id,
                            'agent_id' => $agent->id,
                            'agent_extension' => $agent->extension,
                            'exception' => $e,
                        ]);
                    }
                }

                $this->info("Completed processing tenant {$tenant->name}");

            } catch (\Exception $e) {
                $this->error("Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error("Error processing tenant in DistributorUpdateCallStatus command: {$e->getMessage()}", [
                    'tenant_id' => $tenant->id,
                    'exception' => $e,
                ]);
            }
        }

        $execTime = round(microtime(true) - $startTime, 2);
        $this->info("Dispatched {$jobsDispatched} call status update jobs in total");
        $this->info("Command completed in {$execTime}s");
        
        Log::info("DistributorUpdateCallStatus command completed: {$jobsDispatched} jobs dispatched in {$execTime}s");
    }
}