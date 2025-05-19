<?php

namespace App\Livewire\Systems\Campaign;

use Livewire\Component;
use App\Jobs\FileUploading\ProcessCsvContactsBatch;
use App\Models\Agent;
use Livewire\WithFileUploads;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\SystemLogService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class DistributorCampaignsForm extends Component
{
    use WithFileUploads;

    // Form fields
    public $campaignName;
    public $campaignStart;
    public $campaignEnd;
    public $campaignType = 'distributor'; // Default value
    public $csvFile;

    // Upload state
    public $batchId;
    public $isProcessing = false;
    public $progress = 0;
    public $totalBatches = 0;
    public $processedBatches = 0;
    public $totalContacts = 0;

    public $provider = '';
    public $tenant = '';
    public $agent = '';

    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    public function mount(Provider $provider, Tenant $tenant, Agent $agent)
    {
        $this->provider = $provider;
        $this->tenant = $tenant;
        $this->agent = $agent;
    }

    // Validation rules
    protected $rules = [
        'campaignName' => 'required|string|min:3|max:255',
        'campaignStart' => 'required',
        'campaignEnd' => 'required',
        'campaignType' => 'required|in:dialer,distributor',
        'csvFile' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
    ];

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'file|mimes:csv,txt|max:10240',
        ]);
    }

    public $currentCampaignId = null;
    public $pollingInterval = null;

    public function createDistCampaign()
    {
        $this->validate();

        try {
            if (!$this->csvFile) {
                throw new \Exception('No CSV file uploaded');
            }

            // Store the file permanently
            $path = $this->csvFile->store('csv-imports');
            $fullPath = Storage::path($path);

            // Count records in CSV to initialize progress tracking
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            $totalRecords = count($csv);

            // Create the campaign
            $campaign = Campaign::create([
                'slug' => Str::slug($this->campaignName . '-' . now()->timestamp),
                'name' => $this->campaignName,
                'tenant_id' => auth()->user()->tenant_id,
                'provider_id' => $this->provider->id,
                'agent_id' => $this->agent->id,
                'start_time' => $this->campaignStart,
                'end_time' => $this->campaignEnd,
                'campaign_type' => $this->campaignType,
                'contact_count' => $totalRecords,
            ]);

            // Log campaign creation
            $this->getSystemLogService()->logCreate(
                model: $campaign,
                description: "Created new distributor campaign: {$this->campaignName}",
                metadata: [
                    'total_contacts' => $totalRecords,
                    'campaign_type' => $this->campaignType,
                    'provider_id' => $this->provider->id,
                    'agent_id' => $this->agent->id,
                    'csv_file' => $path,
                ]
            );

            // Dispatch a single job to process the CSV file
            ProcessCsvContactsBatch::dispatch($path, $campaign->id);

            // Log CSV processing started
            $this->getSystemLogService()->log(
                logType: 'process',
                action: 'csv_processing_started',
                model: $campaign,
                description: "Started processing CSV file for distributor campaign: {$this->campaignName}",
                metadata: [
                    'file_path' => $path,
                    'total_records' => $totalRecords,
                    'agent_id' => $this->agent->id
                ]
            );

            // Set initial processing state
            $this->isProcessing = true;
            $this->totalContacts = $totalRecords;
            $this->totalBatches = 1;
            $this->processedBatches = 0;
            $this->progress = 0;
            $this->currentCampaignId = $campaign->id;

            // Start polling for progress updates
            $this->startProgressPolling();

            session()->flash('message', 'Campaign created successfully! Processing contacts in the background.');
            $this->reset(['campaignName', 'campaignStart', 'campaignEnd', 'csvFile']);
        } catch (\Exception $e) {
            // Log error
            $this->getSystemLogService()->log(
                logType: 'error',
                action: 'distributor_campaign_creation_failed',
                description: "Failed to create distributor campaign: {$e->getMessage()}",
                metadata: [
                    'campaign_name' => $this->campaignName,
                    'provider_id' => $this->provider->id,
                    'agent_id' => $this->agent->id,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]
            );

            session()->flash('error', 'Error creating campaign: ' . $e->getMessage());
        }
    }

    public function startProgressPolling()
    {
        // Set up polling interval to check progress - 2 seconds
        $this->pollingInterval = 2000;
    }

    public function checkProgress()
    {
        if (!$this->currentCampaignId) {
            return;
        }

        try {
            $campaign = Campaign::find($this->currentCampaignId);

            if (!$campaign) {
                $this->stopProgressPolling();
                return;
            }

            // Get counts from database
            $processedCount = Contact::where('campaign_id', $this->currentCampaignId)->count();
            $expectedCount = $campaign->total_expected_contacts ?? $this->totalContacts;

            // Update progress state
            $this->processedBatches = 0;
            $this->totalBatches = 1;

            // Calculate progress percentage
            if ($expectedCount > 0) {
                $this->progress = min(round(($processedCount / $expectedCount) * 100), 100);
                $this->totalContacts = $expectedCount;
            } else {
                $this->progress = 0;
                $this->totalContacts = 0;
            }

            // Check if processing is complete
            if ($this->progress >= 100 || $campaign->status === 'processed') {
                $this->isProcessing = false;
                $this->progress = 100;
                $this->stopProgressPolling();

                // Update campaign status if needed
                if ($campaign->status !== 'processed') {
                    $campaign->status = 'processed';
                    $campaign->save();

                    // Log completion
                    $this->getSystemLogService()->log(
                        logType: 'process',
                        action: 'csv_processing_completed',
                        model: $campaign,
                        description: "Completed processing CSV file for distributor campaign: {$campaign->name}",
                        metadata: [
                            'total_processed' => $processedCount,
                            'expected_count' => $expectedCount,
                            'agent_id' => $this->agent->id
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Log error
            $this->getSystemLogService()->log(
                logType: 'error',
                action: 'progress_check_failed',
                description: "Failed to check progress: {$e->getMessage()}",
                metadata: [
                    'campaign_id' => $this->currentCampaignId,
                    'agent_id' => $this->agent->id,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                ]
            );

            Log::error('Error checking progress: ' . $e->getMessage());
        }
    }

    public function stopProgressPolling()
    {
        $this->pollingInterval = null;
    }

    protected function cleanPhoneNumber($number)
    {
        // Remove any non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);

        // Ensure it's a valid phone number format (modify as needed for your requirements)
        if (strlen($number) < 8) {
            return null;
        }

        return $number;
    }

    public function checkBatchProgress()
    {
        if ($this->batchId) {
            $batch = Bus::findBatch($this->batchId);
            if ($batch) {
                $this->processedBatches = $batch->processedJobs();
                $this->progress = $batch->progress();
                $this->isProcessing = !$batch->finished();
            }
        }
    }

    public function render()
    {
        // If we're processing, check the progress
        if ($this->isProcessing && $this->currentCampaignId) {
            $this->checkProgress();
        }
        return view('livewire.systems.campaign.distributor-campaigns-form');
    }
}
