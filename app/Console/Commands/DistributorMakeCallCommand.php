<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\Agent;
// use App\Services\CampaignStatusService;
use App\Services\LicenseService;
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
    $threeCxService = new ThreeCXIntegrationService($tenant->id);

    // Check tenant's license limits
    $license = $tenant->activeLicense();
    if (!$license) {
        Log::warning("No active license for tenant {$tenant->name}");
        return ['processed' => 0, 'calls' => 0];
    }

    // Get active agents with their own campaigns that have new contacts
    $agents = Agent::where('tenant_id', $tenant->id)
        ->where('status', 'active')
        ->whereHas('campaigns', function ($query) {
            $query->where('allow', true)
                ->where('campaign_type', 'distributor')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereHas('contacts', function($contactQuery) {
                    $contactQuery->where('status', 'new');
                });
        })
        ->with(['campaigns' => function($query) {
            $query->where('allow', true)
                ->where('campaign_type', 'distributor')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
                ->whereHas('contacts', function($contactQuery) {
                    $contactQuery->where('status', 'new');
                });
        }])
        ->get();

    // Log agent details for clarity

        // foreach ($agents as $agent) {
        //     $campaignCount = $agent->campaigns->count();
        //     Log::info("******Eligible agent found: {$agent->name} (ID: {$agent->id}, Extension: {$agent->extension}), Campaigns: {$campaignCount}");
        //     foreach ($agent->campaigns as $campaign) {
        //         $newContacts = $campaign->contacts()->where('status', 'new')->count();
        //         Log::info("  - Campaign: {$campaign->name} (ID: {$campaign->id}), New Contacts: {$newContacts}");
        //     }
        // }


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

        // Get campaigns specifically belonging to this agent
        $campaigns = $agent->campaigns()
            ->where('allow', true)
            ->where('campaign_type', 'distributor')
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
            ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
            ->whereHas('contacts', function($query) {
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

            if (!$provider || $provider->status !== 'active') {
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
 * @param Tenant $tenant
 * @param Agent $agent
 * @param Provider $provider
 * @param Campaign $campaign
 * @param ThreeCXIntegrationService $threeCxService
 * @param LicenseService $licenseService
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

    // Get contacts to process for this campaign
    $contacts = Contact::where('campaign_id', $campaign->id)
        ->where('status', 'new')
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

                // Try to get callId from response
                $callId = $callResponse['result']['callid'] ?? null;
                $callStatus = 'initiated';

                // Only mark as calling if we have a successful call response
                if ($callId || isset($callResponse['result']['success'])) {
                    // Mark contact as calling only after successful call initiation
                    $contact->markAsCalling();

                    if (!$callId) {
                        // Refresh active calls to get latest call ID
                        $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($provider->extension);
                        if (isset($refreshCallsResponse['value']) && !empty($refreshCallsResponse['value'])) {
                            $activeCall = $refreshCallsResponse['value'][0];
                            $callId = $activeCall['Id'] ?? null;
                            $callStatus = $activeCall['Status'] ?? null;
                            Log::info("Updated active call - Call ID: {$callId}, Status: {$callStatus}");
                        }
                    }

                    // Log call details
                    $callLog = CallLog::create([
                        'call_id' => $callId ?? 0,
                        'campaign_id' => $campaign->id,
                        'provider_id' => $provider->id,
                        'agent_id' => $agent->id,
                        'contact_id' => $contact->id,
                        'call_status' => $callStatus ?? 'initiated',
                        'call_type' => 'distributor',
                        'called_at' => now(),
                    ]);

                    Log::info("CallLog created with Call ID: {$callId}, Status: {$callStatus}");
                    $campaignCalls++;

                    Log::info("Call initiated: {$contact->phone_number} for tenant {$tenant->id}, agent {$agent->id}, campaign {$campaign->id}");

                    // Add delay between calls for rate limiting
                    usleep($this->callDelay);
                } else {
                    // Call initiation failed
                    Log::error("Failed to initiate call to {$contact->phone_number} for tenant {$tenant->id}");
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
