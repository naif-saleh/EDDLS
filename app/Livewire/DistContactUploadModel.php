<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use App\Jobs\FileUploading\DistProcessCsvContactFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DistContactUploadModel extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $csvFile;
    public $isProcessing = false;
    public $progress = 0;
    public $totalContacts = 0;
    public $fileName;
    public $batchId;
    public $tenantId = '';
    public $pollingInterval = '';

    protected function getListeners()
    {
        return [
            'openAgentContactUploadModal' => 'openModal',
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
    }

    public function openModal()
    {
        $this->reset(['csvFile', 'isProcessing', 'progress', 'batchId', 'totalContacts']);
        $this->showModal = true;
        Log::info('Agent contact upload modal opened');
    }

    public function updatedCsvFile()
    {
        $this->validate([
            'csvFile' => 'file|mimes:csv,txt|max:50000',
        ]);

        if ($this->csvFile) {
            $this->fileName = $this->csvFile->getClientOriginalName();
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
                Cache::put("agent-csv-import-{$this->batchId}-progress", 0, now()->addHours(1));
                Cache::put("agent-csv-import-{$this->batchId}-total", $this->totalContacts, now()->addHours(1));

                // Start job processing
                DistProcessCsvContactFile::dispatch($fullPath, $this->batchId, $this->fileName, $this->tenantId);

                // Start polling for progress updates
                $this->pollingInterval = 2000; // 2 seconds

                Log::info("Agent CSV processing started. Batch ID: {$this->batchId}, Total contacts: {$this->totalContacts}");
            } else {
                $this->addError('csvFile', 'Could not open the uploaded file for processing.');
                Log::error("Failed to open CSV file at path: $fullPath");
            }
        } catch (\Exception $e) {
            $this->addError('csvFile', 'An error occurred: ' . $e->getMessage());
            Log::error("Error in CSV processing: " . $e->getMessage(), [
                'exception' => $e,
                'file' => $this->fileName ?? 'unknown'
            ]);
        }
    }

    public function checkProgress()
    {
        if ($this->batchId) {
            $progress = Cache::get("agent-csv-import-{$this->batchId}-progress", 0);
            $total = Cache::get("agent-csv-import-{$this->batchId}-total", $this->totalContacts);

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
                Cache::forget("agent-csv-import-{$this->batchId}-progress");
                Cache::forget("agent-csv-import-{$this->batchId}-total");

                Log::info("Agent CSV import completed. Batch ID: {$this->batchId}");
            }
        }
    }

    public function render()
    {
        return view('livewire.dist-contact-upload-model');
    }
}
