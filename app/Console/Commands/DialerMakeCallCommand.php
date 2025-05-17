<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\CampaignStatusService;
use App\Services\LicenseService;
use App\Services\ThreeCXIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DialerMakeCallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dialer:make-calls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process dialer calls for all active tenants';

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

        Log::info('Starting dialer calls process at '.now());

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
        $campaignStatusService = new CampaignStatusService;

        if (empty($tenant->setting->start_time) || empty($tenant->setting->end_time)) {
            Log::info("Tenant {$tenant->name} has no start or end time set for dialer calls");

            return ['processed' => 0, 'calls' => 0];
        }

        if (! now()->between($tenant->setting->start_time, $tenant->setting->end_time)) {
            Log::info("Tenant {$tenant->name} is out of time for dialer calls");

            return ['processed' => 0, 'calls' => 0];
        }
        // Load active campaigns for this tenant
        $campaigns = Campaign::where('tenant_id', $tenant->id)
            ->where('allow', true)
            ->where('campaign_type', 'dialer')
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->whereBetween('start_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
            ->whereBetween('end_time', [\Carbon\Carbon::now()->startOfDay(), \Carbon\Carbon::now()->endOfDay()])
            ->get();

        if ($campaigns->isEmpty()) {
            Log::info("No active Dialer campaigns found for tenant {$tenant->name}");

            return ['processed' => 0, 'calls' => 0];
        }

        Log::info('Found Dialer '.$campaigns->count()." active campaigns for tenant {$tenant->name}");

        // Check tenant's license limits
        $license = $tenant->activeLicense();
        if (! $license) {
            Log::warning("No active license for tenant {$tenant->name}");

            return ['processed' => 0, 'calls' => 0];
        }

        // Process each campaign separately
        foreach ($campaigns as $campaign) {
            $provider = Provider::find($campaign->provider_id);

            if (! $provider || $provider->status !== 'active') {
                Log::info("No active provider for Dialer campaign {$campaign->name}");

                continue;
            }

            Log::info("Processing Dialer campaign: {$campaign->name} with provider: {$provider->name}");

            // Update campaign status to 'calling' if it has contacts to process
            $contactsToProcess = Contact::where('campaign_id', $campaign->id)
                ->where('status', 'new')
                ->take($tenant->setting->calls_at_time)
                ->count();

            if ($contactsToProcess > 0) {
                $campaign->status = 'calling';
                $campaign->save();
                Log::info("Campaign {$campaign->name} status set to 'calling'");
            }

            // Initialize the 3CX service for this tenant
            $threeCxService = new ThreeCXIntegrationService($tenant->id);

            // Get contacts to process for this campaign
            $contacts = Contact::where('campaign_id', $campaign->id)
                ->where('status', 'new')
                ->underMaxAttempts(3)
                ->limit($this->maxContactsPerTenant)
                ->get();

            if ($contacts->isEmpty()) {
                Log::info("No contacts to process for campaign {$campaign->name}");

                // Update campaign status if there are no new contacts
                $campaignStatusService->updateSingleCampaignStatus($campaign);

                continue;
            }

            $campaignProcessed = 0;
            $campaignCalls = 0;

            // Process contacts
            foreach ($contacts as $contact) {
                // Check if we've reached rate limits
                if ($campaignCalls >= $this->maxCallsPerMinute) {
                    Log::info("Rate limit reached for campaign {$campaign->name}, pausing");
                    break;
                }

                try {

                    $campaignProcessed++;

                    $licenseSevice = new LicenseService;
                    if ($licenseSevice->validDialCallsCount($tenant->id)) {
                        // Make the actual call
                        $threeCxService->makeCall($provider->extension, $contact->phone_number);
                        // Mark as calling
                        $contact->markAsCalling();
                    } else {
                        Log::error("Tenant {$tenant->name}: License validation failed. Make Calls.");
                        throw new \Exception('License validation failed');
                    }

                    // Wait a moment for the call to be registered in the system
                    usleep(500000); // 500ms delay

                    // Get active calls after initiating the call
                    $refreshCallsResponse = $threeCxService->getActiveCallsForProvider($provider->extension);

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
                        $callLog = CallLog::updateOrCreate(
                            ['call_id' => $callId], // Find by call_id
                            [
                                'campaign_id' => $campaign->id,
                                'provider_id' => $provider->id,
                                'contact_id' => $contact->id,
                                'call_status' => $callStatus ?? 'Unknown',
                                'call_type' => 'dialer',
                                'dial_duration' => 0, // Will be calculated later
                                'talking_duration' => 0, // Will be calculated later
                                'called_at' => $callLog->called_at ?? now(), // Preserve original called_at if updating
                                'updated_at' => now(),
                            ]
                        );

                        Log::info("CallLog upserted with ID: {$callLog->id}, Call ID: {$callId}, Status: {$callStatus}");
                    } else {
                        // If no call_id is available yet, create a placeholder record
                        $callLog = CallLog::create([
                            'call_id' => 0, // Placeholder
                            'campaign_id' => $campaign->id,
                            'provider_id' => $provider->id,
                            'contact_id' => $contact->id,
                            'call_status' => 'Initiated', // Initial status
                            'call_type' => 'dialer',
                            'dial_duration' => 0,
                            'talking_duration' => 0,
                            'called_at' => now(),
                        ]);

                        Log::info("Placeholder CallLog created with ID: {$callLog->id}, Status: Initiated");
                    }

                    $campaignCalls++;

                    // Update campaign status after each call
                    $campaignStatusService->updateSingleCampaignStatus($campaign);

                    Log::info("Call initiated: {$contact->phone_number} for tenant {$tenant->id}, campaign {$campaign->id}");

                    // Add delay between calls for rate limiting
                    usleep($this->callDelay);

                    // Count towards overall totals
                    $processed++;
                    $callsMade++;

                } catch (\Exception $e) {
                    // Handle call failure
                    $contact->completeCall('failed');
                    Log::error("Call failed for contact {$contact->id}: {$e->getMessage()}", [
                        'tenant_id' => $tenant->id,
                        'campaign_id' => $campaign->id,
                        'contact_id' => $contact->id,
                        'exception' => $e,
                    ]);

                    // Update campaign status after failed call
                    $campaignStatusService->updateSingleCampaignStatus($campaign);
                }
            }

            Log::info("Campaign {$campaign->name}: {$campaignProcessed} contacts processed, {$campaignCalls} calls made");

            // Final status update after all contacts are processed
            $campaignStatusService->updateSingleCampaignStatus($campaign);
        }

        return [
            'processed' => $processed,
            'calls' => $callsMade,
        ];
    }
}
