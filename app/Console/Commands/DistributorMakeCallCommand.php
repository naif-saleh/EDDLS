<?php

namespace App\Console\Commands;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\DistributorCallsReport;
use App\Models\Provider;
use App\Models\Tenant;
// use App\Services\CampaignStatusService;
use App\Services\LicenseService;
use App\Services\TenantService;
use App\Services\ThreeCXIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DistributorMakeCallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:distributor-make-call-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process distributor campaigns and make calls following tenant->agent->provider->campaign->contact hierarchy';

    /**
     * Lock timeout in seconds
     *
     * @var int
     */
    protected $lockTimeout = 600;

    /**
     * Minimum time between calls to the same number (minutes)
     *
     * @var int
     */
    protected $duplicateCallWindow = 5;

    /**
     * Maximum calls per minute
     *
     * @var int
     */
    protected $maxCallsPerMinute = 96;

    /**
     * Delay between calls in microseconds
     *
     * @var int
     */
    protected $callDelay = 300000; // 300ms

    /**
     * Maximum contacts to process per tenant in one run
     *
     * @var int
     */
    protected $maxContactsPerTenant = 100;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);

        Log::info('Starting Distributor calls process at '.now());

        $tenants = Tenant::where('status', 'active')
            ->whereHas('licenses', function ($query) {
                $query->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now());
            })
            ->get();

        if ($tenants->isEmpty()) {
            Log::info('No active tenants found');

            return;
        }

        Log::info('Processing '.$tenants->count().' active tenants');
        $totalProcessed = 0;
        $totalCalls = 0;
        $tenantsProcessed = 0;

        foreach ($tenants as $tenant) {
            try {
                Log::info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");
                $result = $this->processTenant($tenant);

                $totalProcessed += $result['processed'];
                $totalCalls += $result['calls'];
                $tenantsProcessed++;

                Log::info("Completed processing tenant {$tenant->name}: {$result['processed']} contacts processed, {$result['calls']} calls made");
            } catch (\Exception $e) {
                $this->error("❌ Error processing tenant {$tenant->name}: {$e->getMessage()}");
                Log::error("Dialer error for tenant {$tenant->id}: {$e->getMessage()}", [
                    'tenant_id' => $tenant->id,
                    'exception' => $e,
                ]);
            }
        }

        $execTime = round(microtime(true) - $startTime, 2);
        Log::info("Completed processing $tenantsProcessed tenants in {$execTime}s");
        Log::info("Summary: $totalProcessed contacts processed, $totalCalls calls made");
    }

    /**
     * Process a single tenant
     *
     * @return array
     */
    protected function processTenant(Tenant $tenant)
    {
        $processed = 0;
        $callsMade = 0;
        $licenseService = new LicenseService;

        // Initialize the 3CX service for this tenant
        $threeCxService = new ThreeCXIntegrationService($tenant);

        if (empty($tenant->setting->start_time) || empty($tenant->setting->end_time)) {
            Log::info("Tenant {$tenant->name} has no start or end time set for dialer calls");

            return ['processed' => 0, 'calls' => 0];
        }

        if (! now()->between($tenant->setting->start_time, $tenant->setting->end_time)) {
            Log::info("Tenant {$tenant->name} is out of time for dialer calls");

            return ['processed' => 0, 'calls' => 0];
        }
        // Check tenant's license limits
        $license = $tenant->activeLicense();
        if (! $license) {
            Log::warning("No active license for tenant {$tenant->name}");

            return ['processed' => 0, 'calls' => 0];
        }

        // Ensure connection is still active for each campaign
            TenantService::setConnection($tenant);
        // Get active agents with their own campaigns that have new contacts
        $agents = Agent::on('tenant')->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->whereHas('campaigns', function ($query) {
                $query->where('allow', true)
                    ->where('campaign_type', 'distributor')
                    ->whereDate('created_at', \Carbon\Carbon::today())
                    ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                    ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                    ->whereHas('contacts', function ($contactQuery) {
                        $contactQuery->where('status', 'new');
                    });
            })
            ->with(['campaigns' => function ($query) {
                $query->where('allow', true)
                    ->where('campaign_type', 'distributor')
                    ->whereDate('created_at', \Carbon\Carbon::today())
                    ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                    ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                    ->whereHas('contacts', function ($contactQuery) {
                        $contactQuery->where('status', 'new');
                    });
            }])
            ->get();

        if ($agents->isEmpty()) {
            Log::info("No active agents with eligible campaigns found for tenant {$tenant->name}");

            return ['processed' => 0, 'calls' => 0];
        }

        Log::info("Processing {$agents->count()} active agents for tenant {$tenant->name}");

        $agentsProcessed = 0;

        // Process each agent
        foreach ($agents as $agent) {
            Log::info("Processing agent: {$agent->name} (ID: {$agent->id})");
            $agentsProcessed++;

            // Skip if agent is already in a call
            if ($threeCxService->isAgentInCall($agent)) {
                Log::info("⚠️ Agent {$agent->name} (ID: {$agent->id}, Extension: {$agent->extension}) is currently in a call. Skipping.");

                continue;
            }

            // Ensure connection is still active for each campaign
            TenantService::setConnection($tenant);
            // Get campaigns specifically belonging to this agent
            $campaigns = $agent->campaigns()
                ->where('allow', true)
                ->where('campaign_type', 'distributor')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereHas('contacts', function ($query) {
                    $query->where('status', 'new');
                })
                ->get();

            if ($campaigns->isEmpty()) {
                Log::info("No eligible campaigns found for agent {$agent->name}");

                continue;
            }

            Log::info("Processing {$campaigns->count()} campaigns for agent {$agent->name}");
            $campaignsProcessed = 0;

            // Process each campaign for this agent
            foreach ($campaigns as $campaign) {
                $campaignsProcessed++;
                Log::info("Processing campaign: {$campaign->name} (ID: {$campaign->id})");

                // Get the provider for this campaign
                $provider = $campaign->provider;

                if (! $provider || $provider->status !== 'active') {
                    Log::info("No active provider found for campaign {$campaign->name}");

                    continue;
                }

                Log::info("Using provider: {$provider->name} (ID: {$provider->id})");

                $result = $this->processCampaign($tenant, $agent, $provider, $campaign, $threeCxService, $licenseService);
                $processed += $result['processed'];
                $callsMade += $result['calls'];

                // Break out of the campaign loop if max calls reached
                if ($callsMade >= $this->maxCallsPerMinute) {
                    Log::info("Rate limit reached for tenant {$tenant->name}, pausing");
                    break 2; // Break out of all loops
                }
            }

            Log::info("Completed processing {$campaignsProcessed} campaigns for agent {$agent->name}");
        }

        Log::info("Tenant {$tenant->name} summary: {$agentsProcessed} agents processed");

        return [
            'processed' => $processed,
            'calls' => $callsMade,
        ];
    }

    /**
     * Process a single campaign
     *
     * @return array
     */
    protected function processCampaign(
        Tenant $tenant,
        Agent $agent,
        Provider $provider,
        Campaign $campaign,
        ThreeCXIntegrationService $threeCxService,
        LicenseService $licenseService
    ) {
        $campaignProcessed = 0;
        $campaignCalls = 0;
        $contactsProcessed = 0;

        Log::info("Processing Distributor campaign: {$campaign->name} with provider: {$provider->name} for agent: {$agent->name}");

        // Ensure connection is still active for each campaign
            TenantService::setConnection($tenant);
        // Get contacts to process for this campaign
        $contacts = Contact::on('tenant')->where('campaign_id', $campaign->id)
            ->where('status', 'new')
            ->take($tenant->setting->calls_at_time)
            ->get();

        if ($contacts->isEmpty()) {
            Log::info("No new contacts to process for campaign {$campaign->name}");

            return ['processed' => 0, 'calls' => 0];
        }

        Log::info("Processing {$contacts->count()} contacts for campaign {$campaign->name}");

        // Process contacts
        foreach ($contacts as $contact) {
            $contactsProcessed++;
            Log::info("Processing contact: ID: {$contact->id}, Phone: {$contact->phone_number}");

            // Check if we've reached rate limits
            if ($campaignCalls >= $this->maxCallsPerMinute) {
                Log::info("Rate limit reached for campaign {$campaign->name}, pausing");
                break;
            }

            // Skip if agent is now in call
            if ($threeCxService->isAgentInCall($agent)) {
                Log::info("⚠️ Agent {$agent->name} ({$agent->extension}) is now in a call. Skipping remaining contacts.");
                break;
            }

            try {
                $campaignProcessed++;

                // Check license validity for making calls
                if ($licenseService->validDistCallsCount($tenant->id)) {
                    // Make the actual call through the agent
                    Log::info("Initiating call to {$contact->phone_number} via agent {$agent->name} (Extension: {$agent->extension})");
                    $callResponse = $threeCxService->makeCallDist($agent, $contact->phone_number);
                    // Mark contact as calling only after successful call initiation
                    $contact->markAsCalling();
                    // Mark contact as calling only after successful call initiation
                    // Log the raw response for debugging
                    Log::debug('Raw call response:', ['response' => $callResponse]);

                    // Add a short delay to ensure the call is registered in the system
                    usleep(500000); // 500ms delay

                    // Get active calls after initiating the call
                    $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($agent->extension);

                    // Initialize variables
                    $callId = null;
                    $callStatus = null;
                    // Process active call data if available
                    if (isset($refreshCallsResponse['value']) && ! empty($refreshCallsResponse['value'])) {
                        // Find the most recent call (usually the first one in the array)
                        $activeCall = $refreshCallsResponse['value'][0];
                        $callId = $activeCall['Id'] ?? null;
                        $callStatus = $activeCall['Status'] ?? null;

                        // Log complete active call information for debugging
                        Log::info('Active call details:', [
                            'call_id' => $callId,
                            'status' => $callStatus,
                            'caller' => $activeCall['Caller'] ?? 'Unknown',
                            'callee' => $activeCall['Callee'] ?? 'Unknown',
                            'established_at' => $activeCall['EstablishedAt'] ?? null,
                            'last_status_change' => $activeCall['LastChangeStatus'] ?? null,
                        ]);
                    } else {
                        Log::warning("No active calls found for provider {$provider->extension} after initiating call");
                    }

                    // Upsert call log entry - create if not exists, update if exists
                    if ($callId) {

                        // Ensure connection is still active for each campaign
                        TenantService::setConnection($tenant);
                        DistributorCallsReport::on('tenant')->create([
                            'tenant_id' => $tenant->id,

                            'call_id' => $callId,
                            'date_time' => now(),
                            'agent' => $agent->name,
                            'provider' => $provider->name,
                            'campaign' => $campaign->name,
                            'phone_number' => $contact->phone_number,
                            'call_status' => $callStatus ?? 'Unknown',
                            'dialing_duration' => 'null', // Placeholder
                            'talking_duration' => 'null', // Placeholder
                            'call_at' => now(),
                        ]);

                        Log::info("Distributor call log created for contact {$contact->id}: {$contact->phone_number}, Call ID: {$callId}");
                    }

                } else {
                    Log::error("Tenant {$tenant->name}: License validation failed. Cannot make calls.");
                }
            } catch (\Exception $e) {
                // Handle call failure
                Log::error("Call failed for contact {$contact->id}: {$e->getMessage()}", [
                    'tenant_id' => $tenant->id,
                    'agent_id' => $agent->id,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'exception' => $e,
                ]);
            }
        }

        Log::info("Campaign {$campaign->name} completed: {$contactsProcessed} contacts processed, {$campaignProcessed} contacts attempted, {$campaignCalls} calls made");

        return [
            'processed' => $campaignProcessed,
            'calls' => $campaignCalls,
        ];
    }
}
