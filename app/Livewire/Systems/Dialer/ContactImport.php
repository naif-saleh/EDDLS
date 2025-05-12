<?php

namespace App\Http\Livewire\Contacts;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContactImporter extends Component
{
    use WithFileUploads;

    public $csvFile;

    public $isProcessing = false;

    public $progress = 0;

    public $totalContacts = 0;

    public $pollingInterval = null;

    public $processingId = null;

    public bool $showModal = false;

    protected $listeners = ['openContactImport' => 'show'];

    public function show()
    {
        $this->showModal = true;
    }

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
    ];

    public function mount()
    {
        $this->processingId = Str::uuid();
    }

    public function updatedCsvFile()
    {
        $this->validate();
        $this->processCSV();
    }

    public function processCSV()
    {
        $this->validate();

        $this->isProcessing = true;
        $this->progress = 0;

        $path = $this->csvFile->getRealPath();
        $file = fopen($path, 'r');

        // Read and validate header row
        $header = fgetcsv($file);

        // Convert headers to lowercase and trim whitespace for consistency
        $header = array_map(function ($item) {
            return trim(strtolower($item));
        }, $header);

        // Check if the required columns exist in the CSV
        $requiredColumns = ['phone_number', 'provider_name', 'provider_extension', 'start_time', 'end_time'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (! empty($missingColumns)) {
            $this->addError('csvFile', 'CSV is missing required columns: '.implode(', ', $missingColumns));
            fclose($file);
            $this->isProcessing = false;

            return;
        }

        // Count total contacts for progress tracking
        $lineCount = 0;
        while (fgetcsv($file) !== false) {
            $lineCount++;
        }
        $this->totalContacts = $lineCount;

        // Reset file pointer
        rewind($file);
        fgetcsv($file); // Skip header row

        // Start processing in chunks
        $batchSize = 100;
        $currentRow = 0;
        $batch = [];

        // Store batch info in cache for background processing
        $cacheKey = "contact-import-{$this->processingId}";
        cache()->put($cacheKey, [
            'total' => $this->totalContacts,
            'processed' => 0,
        ], now()->addHours(1));

        // Start polling for progress updates
        $this->pollingInterval = 1000; // 1 second

        // Process file in batches to prevent timeouts
        DB::transaction(function () use ($file, $header, $batchSize, &$currentRow, &$batch) {
            $tenantId = Auth::user()->tenant_id;
            $providerCache = [];
            $campaignCache = [];

            while (($row = fgetcsv($file)) !== false) {
                $currentRow++;

                // Create an associative array from row data
                $data = array_combine($header, $row);

                // Clean and validate phone number
                $phoneNumber = preg_replace('/[^0-9]/', '', $data['phone_number']);
                if (empty($phoneNumber)) {
                    continue; // Skip invalid phone numbers
                }

                // Get or create provider
                $providerKey = $data['provider_name'].'_'.$data['provider_extension'];
                if (! isset($providerCache[$providerKey])) {
                    $provider = Provider::firstOrCreate(
                        [
                            'name' => $data['provider_name'],
                            'extension' => $data['provider_extension'],
                            'tenant_id' => $tenantId,
                        ],
                        [
                            'slug' => Str::slug($data['provider_name'].'-'.$data['provider_extension'].'-'.uniqid()),
                            'status' => 'active',
                            'provider_type' => 'dialer', // Default type, adjust as needed
                        ]
                    );
                    $providerCache[$providerKey] = $provider->id;
                }
                $providerId = $providerCache[$providerKey];

                // Parse dates
                try {
                    $startTime = Carbon::parse($data['start_time']);
                    $endTime = Carbon::parse($data['end_time']);
                } catch (\Exception $e) {
                    // Skip if dates are invalid
                    continue;
                }

                // Create or get campaign
                $campaignKey = $providerId.'_'.$startTime->format('YmdHis').'_'.$endTime->format('YmdHis');
                if (! isset($campaignCache[$campaignKey])) {
                    $campaignName = $data['provider_name'].' '.$startTime->format('Y-m-d H:i');
                    $campaign = Campaign::firstOrCreate(
                        [
                            'provider_id' => $providerId,
                            'tenant_id' => $tenantId,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ],
                        [
                            'slug' => Str::slug($campaignName.'-'.uniqid()),
                            'name' => $campaignName,
                            'status' => 'not_start',
                            'campaign_type' => 'dialer', // Default type, adjust as needed
                            'contact_count' => 0,
                        ]
                    );
                    $campaignCache[$campaignKey] = $campaign->id;
                }
                $campaignId = $campaignCache[$campaignKey];

                // Add contact to batch
                $batch[] = [
                    'slug' => Str::slug('contact-'.$phoneNumber.'-'.uniqid()),
                    'campaign_id' => $campaignId,
                    'phone_number' => $phoneNumber,
                    'status' => 'new',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Insert batch if needed
                if (count($batch) >= $batchSize) {
                    Contact::insert($batch);

                    // Update campaign contact count
                    foreach (array_count_values(array_column($batch, 'campaign_id')) as $campId => $count) {
                        Campaign::where('id', $campId)->increment('contact_count', $count);
                    }

                    // Update progress
                    $this->updateProgress($currentRow);

                    // Clear batch
                    $batch = [];
                }
            }

            // Insert remaining contacts
            if (count($batch) > 0) {
                Contact::insert($batch);

                // Update campaign contact count
                foreach (array_count_values(array_column($batch, 'campaign_id')) as $campId => $count) {
                    Campaign::where('id', $campId)->increment('contact_count', $count);
                }

                // Update final progress
                $this->updateProgress($currentRow);
            }

            // Mark as completed
            $this->completeImport();
        });

        fclose($file);
    }

    private function updateProgress($processed)
    {
        $cacheKey = "contact-import-{$this->processingId}";
        cache()->put($cacheKey, [
            'total' => $this->totalContacts,
            'processed' => $processed,
        ], now()->addHours(1));

        // Calculate progress percentage
        if ($this->totalContacts > 0) {
            $this->progress = min(round(($processed / $this->totalContacts) * 100), 100);
        } else {
            $this->progress = 100;
        }
    }

    public function checkProgress()
    {
        $cacheKey = "contact-import-{$this->processingId}";
        $stats = cache()->get($cacheKey);

        if ($stats) {
            if ($stats['total'] > 0) {
                $this->progress = min(round(($stats['processed'] / $stats['total']) * 100), 100);
            } else {
                $this->progress = 100;
            }

            // If completed, stop polling
            if ($this->progress >= 100) {
                $this->completeImport();
            }
        }
    }

    private function completeImport()
    {
        $this->isProcessing = false;
        $this->pollingInterval = null;
        $this->dispatchBrowserEvent('import-completed');
        $this->emit('contactsImported');

        // Clean up cache
        $cacheKey = "contact-import-{$this->processingId}";
        cache()->forget($cacheKey);
    }
    public function render()
    {
        return view('livewire.systems.dialer.contact-import');
    }
}
