<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Jobs\FileUploading\ProcessCsvContactsBatchFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ContactUploadModal extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $csvFile;
    public $isProcessing = false;
    public $progress = 0;
    public $jobBatchId;
    public $pollingInterval = null;
    public $totalContacts = 0;
    public $fileName;
    public $batchId;
    public $tenantId = '';

    protected function getListeners()
    {
        return [
            'openContactUploadModal' => 'openModal',
            'updateProgress' => 'checkProgress',
        ];
    }

    protected $rules = [
        'csvFile' => 'required|file|mimes:csv,txt|max:50000',
    ];

    public function mount()
{
    $this->showModal = false;
    $this->tenantId = auth()->user()->tenant_id;

    // Add debug logging to verify tenant ID
    Log::info("Modal mounted for tenant ID: {$this->tenantId}");
}

    public function openModal()
    {
        $this->reset(['csvFile', 'isProcessing', 'progress', 'jobBatchId', 'totalContacts']);
        $this->showModal = true;
        // Log for debugging
        Log::info('Upload modal opened');
    }

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'file|mimes:csv,txt|max:50000',
        ]);

        if ($this->csvFile) {
            $this->fileName = $this->csvFile->getClientOriginalName();
            // Log for debugging
            Log::info('CSV file uploaded: ' . $this->fileName);
        }
    }

    public function processCSV()
    {
        if (!$this->csvFile) {
            $this->addError('csvFile', 'Please select a file to upload.');
            return;
        }

        $this->validate();

        try {
            // Verify tenant ID is set and log it
            if (empty($this->tenantId)) {
                $this->tenantId = auth()->user()->tenant_id;
                Log::warning("Tenant ID was empty, set to: {$this->tenantId}");
            }

            // Generate a unique batch ID for tracking this import
            $this->batchId = (string) Str::uuid();

            // Store file temporarily
            $path = $this->csvFile->store('csv-imports');
            $fullPath = Storage::path($path);

            // Count total lines in CSV (excluding header)
            $lineCount = -1; // Start at -1 to exclude header
            $file = fopen($fullPath, 'r');
            if ($file) {
                while(!feof($file)) {
                    if(fgets($file)) $lineCount++;
                }
                fclose($file);

                $this->totalContacts = max(0, $lineCount);
                $this->isProcessing = true;
                $this->progress = 0;

                // Initialize progress tracking
                Cache::put("csv-import-{$this->batchId}-progress", 0, now()->addHours(1));
                Cache::put("csv-import-{$this->batchId}-total", $this->totalContacts, now()->addHours(1));

                // Start batch job processing with explicit tenant ID
                Log::info("Dispatching CSV job with: batch={$this->batchId}, file={$this->fileName}, tenant={$this->tenantId}");
                ProcessCsvContactsBatchFile::dispatch($fullPath, $this->batchId, $this->fileName, $this->tenantId);

                // Start polling for progress updates
                $this->pollingInterval = 2000; // 2 seconds
            } else {
                $this->addError('csvFile', 'Could not open the uploaded file for processing.');
                Log::error("Failed to open CSV file at path: $fullPath");
            }
        } catch (\Exception $e) {
            $this->addError('csvFile', 'An error occurred: ' . $e->getMessage());
            Log::error("Error in CSV processing: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $this->fileName ?? 'unknown',
                'tenant_id' => $this->tenantId
            ]);
        }
    }

    public function checkProgress()
    {
        if ($this->batchId) {
            $progress = Cache::get("csv-import-{$this->batchId}-progress", 0);
            $total = Cache::get("csv-import-{$this->batchId}-total", $this->totalContacts);

            // Calculate percentage
            if ($total > 0) {
                $this->progress = round(($progress / $total) * 100);
            }

            // Check if completed
            if ($progress >= $total && $total > 0) {
                $this->isProcessing = false;
                $this->pollingInterval = null;
                $this->dispatch('import-completed');

                // Clean up cache
                Cache::forget("csv-import-{$this->batchId}-progress");
                Cache::forget("csv-import-{$this->batchId}-total");

                // Log completion
                Log::info("CSV import completed. Batch ID: {$this->batchId}");
            }
        }
    }

    public function render()
    {
        return view('livewire.contact-upload-modal');
    }
}
