<?php

namespace App\Jobs\FileUploading;

use App\Models\Contact;
use App\Models\Campaign;
use League\Csv\Reader;
use Illuminate\Bus\Queueable;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCsvContactsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $campaignId;

    /**
     * Create a new job instance.
     *
     * @param string $filePath
     * @param int $campaignId
     * @return void
     */
    public function __construct(string $filePath, int $campaignId)
    {
        $this->filePath = $filePath;
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            if (!Storage::exists($this->filePath)) {
                Log::error('CSV file not found at: ' . $this->filePath);
                return;
            }

            $fullPath = Storage::path($this->filePath);

            // Read the CSV
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0); // Set to null if no header row

            // Count total records for progress tracking
            $totalRecords = count($csv);

            // Set batch size - adjust based on your needs
            $batchSize = 1000;

            // Prepare batches
            $batches = [];
            $records = $csv->getRecords();
            $batch = [];
            $count = 0;

            foreach ($records as $record) {
                // Get phone number - either from a column name or the first column if no header
                $phoneNumber = isset($record['phone_number'])
                    ? $record['phone_number']
                    : reset($record);

                // Clean the phone number
                $phoneNumber = $this->cleanPhoneNumber($phoneNumber);

                if (empty($phoneNumber)) {
                    continue; // Skip invalid numbers
                }

                $batch[] = [
                    'campaign_id' => $this->campaignId,
                    'phone_number' => $phoneNumber,
                    'slug' => Str::uuid()->toString(),
                    'status' => 'new',
                    'attempt_count' => 0,
                    'created_at' => now()
                ];

                $count++;

                // When batch is full, directly insert instead of creating more jobs
                if ($count % $batchSize === 0) {
                    $this->insertContacts($batch);
                    $batch = [];
                }
            }

            // Insert any remaining records
            if (!empty($batch)) {
                $this->insertContacts($batch);
            }

            // Update campaign status to complete
            $campaign = Campaign::find($this->campaignId);
            if ($campaign) {
                $campaign->status = 'processed';
                $campaign->contact_count = Contact::where('campaign_id', $this->campaignId)->count();
                $campaign->save();
            }

            // Delete the file once processed
            Storage::delete($this->filePath);

            Log::info('CSV processing completed for campaign: ' . $this->campaignId);
        } catch (\Exception $e) {
            Log::error('Error processing CSV file: ' . $e->getMessage(), [
                'file' => $this->filePath,
                'campaign_id' => $this->campaignId,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Insert contacts in a batch
     *
     * @param array $contacts
     * @return void
     */
    protected function insertContacts(array $contacts)
    {
        try {
            Contact::insert($contacts);
        } catch (\Exception $e) {
            Log::error('Error inserting contacts batch: ' . $e->getMessage(), [
                'campaign_id' => $this->campaignId,
                'batch_size' => count($contacts),
            ]);
        }
    }

    /**
     * Clean a phone number
     *
     * @param string $number
     * @return string|null
     */
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
}
