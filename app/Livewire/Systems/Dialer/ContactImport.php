<?php

namespace App\Http\Livewire\Contacts;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use App\Services\SystemLogService;
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

    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    protected $listeners = ['openContactImport' => 'show'];

    public function show()
    {
        $this->showModal = true;
        
        // Log modal open
        $this->getSystemLogService()->log(
            logType: 'ui_action',
            action: 'open_contact_import',
            description: 'Opened contact import modal'
        );
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

        try {
            $this->isProcessing = true;
            $this->progress = 0;

            $path = $this->csvFile->getRealPath();
            $file = fopen($path, 'r');

            // Read and validate header row
            $header = fgetcsv($file);
            $header = array_map(function ($item) {
                return trim(strtolower($item));
            }, $header);

            // Check required columns
            $requiredColumns = ['phone_number', 'provider_name', 'provider_extension', 'start_time', 'end_time'];
            $missingColumns = array_diff($requiredColumns, $header);

            if (!empty($missingColumns)) {
                $this->addError('csvFile', 'CSV is missing required columns: '.implode(', ', $missingColumns));
                
                // Log validation error
                $this->getSystemLogService()->log(
                    logType: 'validation',
                    action: 'contact_import_validation_failed',
                    description: 'CSV file missing required columns',
                    metadata: [
                        'missing_columns' => $missingColumns,
                        'file_name' => $this->csvFile->getClientOriginalName()
                    ]
                );
                
                fclose($file);
                $this->isProcessing = false;
                return;
            }

            // Count total contacts
            $lineCount = 0;
            while (fgetcsv($file) !== false) {
                $lineCount++;
            }
            $this->totalContacts = $lineCount;

            // Log import start
            $this->getSystemLogService()->log(
                logType: 'import',
                action: 'contact_import_started',
                description: 'Started processing contact import',
                metadata: [
                    'file_name' => $this->csvFile->getClientOriginalName(),
                    'total_contacts' => $this->totalContacts,
                    'processing_id' => $this->processingId
                ]
            );

            // Reset file pointer
            rewind($file);
            fgetcsv($file); // Skip header row

            // Process file in batches
            DB::transaction(function () use ($file, $header) {
                $tenantId = Auth::user()->tenant_id;
                $providerCache = [];
                $campaignCache = [];
                $batchSize = 100;
                $currentRow = 0;
                $batch = [];
                $processedProviders = [];
                $processedCampaigns = [];

                while (($row = fgetcsv($file)) !== false) {
                    $currentRow++;
                    $data = array_combine($header, $row);

                    // Process provider
                    $providerKey = $data['provider_name'].'_'.$data['provider_extension'];
                    if (!isset($providerCache[$providerKey])) {
                        $provider = Provider::firstOrCreate(
                            [
                                'name' => $data['provider_name'],
                                'extension' => $data['provider_extension'],
                                'tenant_id' => $tenantId,
                            ],
                            [
                                'slug' => Str::slug($data['provider_name'].'-'.$data['provider_extension'].'-'.uniqid()),
                                'status' => 'active',
                                'provider_type' => 'dialer',
                            ]
                        );
                        $providerCache[$providerKey] = $provider->id;
                        $processedProviders[] = $provider;
                    }

                    // Process campaign
                    try {
                        $startTime = Carbon::parse($data['start_time']);
                        $endTime = Carbon::parse($data['end_time']);
                        
                        $campaignKey = $providerCache[$providerKey].'_'.$startTime->format('YmdHis').'_'.$endTime->format('YmdHis');
                        if (!isset($campaignCache[$campaignKey])) {
                            $campaignName = $data['provider_name'].' '.$startTime->format('Y-m-d H:i');
                            $campaign = Campaign::firstOrCreate(
                                [
                                    'provider_id' => $providerCache[$providerKey],
                                    'tenant_id' => $tenantId,
                                    'start_time' => $startTime,
                                    'end_time' => $endTime,
                                ],
                                [
                                    'slug' => Str::slug($campaignName.'-'.uniqid()),
                                    'name' => $campaignName,
                                    'status' => 'not_start',
                                    'campaign_type' => 'dialer',
                                    'contact_count' => 0,
                                ]
                            );
                            $campaignCache[$campaignKey] = $campaign->id;
                            $processedCampaigns[] = $campaign;
                        }

                        // Add contact to batch
                        $phoneNumber = preg_replace('/[^0-9]/', '', $data['phone_number']);
                        if (!empty($phoneNumber)) {
                            $batch[] = [
                                'slug' => Str::slug('contact-'.$phoneNumber.'-'.uniqid()),
                                'campaign_id' => $campaignCache[$campaignKey],
                                'phone_number' => $phoneNumber,
                                'status' => 'new',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }

                        // Process batch if needed
                        if (count($batch) >= $batchSize) {
                            $this->processBatch($batch, $campaignCache);
                            $this->updateProgress($currentRow);
                            $batch = [];
                        }
                    } catch (\Exception $e) {
                        // Log date parsing error
                        $this->getSystemLogService()->log(
                            logType: 'error',
                            action: 'contact_import_date_parse_error',
                            description: 'Failed to parse dates in contact import',
                            metadata: [
                                'row_number' => $currentRow,
                                'error' => $e->getMessage(),
                                'data' => $data
                            ]
                        );
                        continue;
                    }
                }

                // Process remaining contacts
                if (count($batch) > 0) {
                    $this->processBatch($batch, $campaignCache);
                    $this->updateProgress($currentRow);
                }

                // Log import completion
                $this->getSystemLogService()->log(
                    logType: 'import',
                    action: 'contact_import_completed',
                    description: 'Completed processing contact import',
                    metadata: [
                        'total_processed' => $currentRow,
                        'providers_created' => count($processedProviders),
                        'campaigns_created' => count($processedCampaigns),
                        'processing_id' => $this->processingId
                    ]
                );
            });

            fclose($file);
        } catch (\Exception $e) {
            // Log import error
            $this->getSystemLogService()->log(
                logType: 'error',
                action: 'contact_import_failed',
                description: 'Contact import process failed',
                metadata: [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString(),
                    'processing_id' => $this->processingId
                ]
            );
            throw $e;
        }
    }

    private function processBatch(array $batch, array $campaignCache)
    {
        Contact::insert($batch);

        // Update campaign contact counts
        foreach (array_count_values(array_column($batch, 'campaign_id')) as $campId => $count) {
            Campaign::where('id', $campId)->increment('contact_count', $count);
        }
    }

    private function updateProgress($processed)
    {
        $cacheKey = "contact-import-{$this->processingId}";
        cache()->put($cacheKey, [
            'total' => $this->totalContacts,
            'processed' => $processed,
        ], now()->addHours(1));

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
