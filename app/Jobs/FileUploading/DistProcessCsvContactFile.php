<?php

namespace App\Jobs\FileUploading;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use App\Models\SkippedNumber;
use Generator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DistProcessCsvContactFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    protected $batchId;

    protected $fileName;

    protected $tenantId;

    protected $chunkSize = 1000;

    // Store cached agent IDs
    protected $agentCache = [];

    // Store campaign and provider mappings
    protected $campaignCache = [];

    protected $providerCache = [];

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $batchId, string $fileName, int $tenantId)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->fileName = $fileName;
        $this->tenantId = $tenantId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Starting agent-based CSV import for batch: {$this->batchId}, file: {$this->fileName}");

        if (! file_exists($this->filePath)) {
            Log::error("File not found: {$this->filePath}");

            return;
        }

        // Pre-load all agents for this tenant into memory to avoid repeated DB queries
        $this->preloadAgents();

        // If no agents were found for this tenant, log error and exit early
        if (empty($this->agentCache)) {
            Log::error("No agents found for tenant {$this->tenantId}. CSV import canceled.");
            unlink($this->filePath);

            return;
        }

        // Process the file using generators to minimize memory usage
        try {
            $processedCount = 0;
            $skippedCount = 0;

            // Validate the header
            $header = $this->validateHeaderRow();
            if (! $header) {
                unlink($this->filePath);

                return;
            }

            // Process the file in chunks
            foreach ($this->getChunkedData($header) as $chunk) {
                if (empty($chunk['data'])) {
                    continue;
                }

                // Insert the chunk
                $this->insertContacts($chunk['data']);
                $processedCount += $chunk['processed'];
                $skippedCount += $chunk['skipped'];

                // Update progress
                $this->updateProgress($processedCount);

                // Free memory
                unset($chunk);
            }

            Log::info("Agent CSV import completed for batch {$this->batchId}. Processed: {$processedCount}, Skipped: {$skippedCount}");
        } catch (\Exception $e) {
            Log::error('Error processing CSV: '.$e->getMessage(), [
                'exception' => $e,
                'batch_id' => $this->batchId,
            ]);
        } finally {
            // Clean up the file regardless of success or failure
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
        }
    }

    /**
     * Pre-load all agents for this tenant into memory
     */
    protected function preloadAgents()
    {
        // Load all agents for this tenant to minimize database queries
        $agents = Agent::where('tenant_id', $this->tenantId)
            ->select('id', 'extension')
            ->get();

        foreach ($agents as $agent) {
            $this->agentCache[$agent->extension] = $agent->id;
        }

        Log::info("Preloaded {$agents->count()} agents for tenant {$this->tenantId}");
    }

    /**
     * Validate the header row of the CSV
     */
    protected function validateHeaderRow()
    {
        $file = fopen($this->filePath, 'r');
        if (! $file) {
            Log::error("Could not open file: {$this->filePath}");

            return null;
        }

        // Read header
        $header = fgetcsv($file);
        fclose($file);

        if (! $header) {
            Log::error("Empty or invalid CSV file: {$this->filePath}");

            return null;
        }

        // Trim header values
        $header = array_map('trim', $header);

        // Check for required columns
        $requiredColumns = ['file_name', 'provider_name', 'provider_extension', 'agent_extension', 'phone_number', 'start_time', 'end_time'];
        foreach ($requiredColumns as $column) {
            if (! in_array($column, $header)) {
                Log::error("Missing required column '{$column}' in CSV file");

                return null;
            }
        }

        return $header;
    }

    /**
     * Generate chunks of data from the CSV file
     */
    protected function getChunkedData(array $header): Generator
    {
        $file = fopen($this->filePath, 'r');

        // Skip header
        fgetcsv($file);

        $chunk = [];
        $processedInChunk = 0;
        $skippedInChunk = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;
            // Skip empty rows
            if (empty($row) || count(array_filter($row)) === 0) {
                $skippedInChunk++;

                continue;
            }
            $partialData = array_combine(
                array_slice($header, 0, min(count($header), count($row))),
                array_slice($row, 0, min(count($header), count($row)))
            );

            $fileName = $partialData['file_name'] ?? null;
            $providerName = $partialData['provider_name'] ?? null;
            $providerExtension = $partialData['provider_extension'] ?? null;

            // Try to find provider ID if we have provider info
            $providerId = null;
            if ($providerName || $providerExtension) {
                $provider = Provider::where('name', $providerName)
                    ->orWhere('extension', $providerExtension)
                    ->first();
                if ($provider) {
                    $providerId = $provider->id;
                }
            }

            // Try to find campaign ID if we have file name and provider info
            $campaignId = null;
            if ($fileName && $providerId) {
                $campaign = Campaign::where('name', $fileName)
                    ->where('provider_id', $providerId)
                    ->first();
                if ($campaign) {
                    $campaignId = $campaign->id;
                }
            }
            // Check if row count matches header count
            if (count($row) !== count($header)) {
                Log::warning("Skipping malformed row at line {$rowNumber} mobile ");
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "Skipping malformed row at line {$rowNumber} mobile ",
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }
                $skippedInChunk++;

                continue;
            }

            // Process the row
            $row = array_map('trim', $row);
            $data = array_combine($header, $row);

            if (empty($data['file_name'])) {
                Log::warning("File name is missing row at line {$rowNumber} mobile ".$data['phone_number']);
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "File name is missing row at line {$rowNumber} mobile ".$data['phone_number'],
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }
                $skippedInChunk++;

                continue;
            } elseif (empty($data['provider_name'])) {
                Log::warning("Provider name is missing row at line {$rowNumber} mobile ".$data['phone_number']);
                $skippedInChunk++;
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "Provider name is missing row at line {$rowNumber} mobile ".$data['phone_number'],
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }

                continue;
            } elseif (empty($data['provider_extension'])) {
                Log::warning("Provider extensio is missing row at line {$rowNumber} mobile ".$data['phone_number']);
                $skippedInChunk++;
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "Provider extensio is missing row at line {$rowNumber} mobile ".$data['phone_number'],
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }

                continue;
            } elseif (empty($data['agent_extension'])) {
                Log::warning("Agent extension is missing row at line {$rowNumber} mobile ".$data['phone_number']);
                $skippedInChunk++;
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "Agent extension is missing row at line {$rowNumber} mobile ".$data['phone_number'],
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }

                continue;
            }

            // Skip if agent doesn't exist
            $agentExtension = $data['agent_extension'];
            if (! isset($this->agentCache[$agentExtension])) {
                Log::warning("Agent with extension {$agentExtension} not found. Skipping row {$rowNumber}");
                $skippedInChunk++;
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $agentId ?? null,
                        $campaignId ?? null,
                        "Agent with extension {$agentExtension} not found. Skipping row {$rowNumber}",
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error('Error saving skipped number: '.$saveEx->getMessage());
                }

                continue;
            }

            $agentId = $this->agentCache[$agentExtension];
            $fileName = $data['file_name'];
            $providerName = $data['provider_name'];
            $providerExtension = $data['provider_extension'];
            // Get campaign ID (create if needed)
            $campaignId = $this->getCampaignId($fileName, $providerName, $providerExtension, $agentId, $data);

            // Add to chunk
            $chunk[] = [
                'phone_number' => $data['phone_number'],
                'slug' => Str::slug($fileName.'-'.$data['phone_number']),
                'campaign_id' => $campaignId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $processedInChunk++;

            // If chunk is full, yield it
            if (count($chunk) >= $this->chunkSize) {
                yield [
                    'data' => $chunk,
                    'processed' => $processedInChunk,
                    'skipped' => $skippedInChunk,
                ];

                $chunk = [];
                $processedInChunk = 0;
                $skippedInChunk = 0;
            }
        }

        // Yield remaining data
        if (! empty($chunk)) {
            yield [
                'data' => $chunk,
                'processed' => $processedInChunk,
                'skipped' => $skippedInChunk,
            ];
        }

        fclose($file);
    }

    /**
     * Get campaign ID (create if needed)
     */
    protected function getCampaignId($fileName, $providerName, $providerExtension, $agentId, $data)
    {
        $campaignKey = $fileName.'|'.$agentId;

        if (! isset($this->campaignCache[$campaignKey])) {
            // Get provider first (or create it)
            $providerId = $this->getProviderId($providerName, $providerExtension);

            // Create or get campaign using transaction to prevent race conditions
            DB::beginTransaction();
            try {
                $campaign = Campaign::firstOrCreate(
                    [
                        'name' => $fileName, 'agent_id' => $agentId,
                        'slug' => Str::slug($fileName.'-'.$agentId),
                    ],
                    [
                        'tenant_id' => $this->tenantId,
                        'provider_id' => $providerId,
                        'agent_id' => $agentId,
                        'name' => $fileName,
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'campaign_type' => 'distributor',
                    ]
                );
                DB::commit();

                $this->campaignCache[$campaignKey] = $campaign->id;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error creating campaign: '.$e->getMessage());
                throw $e;
            }
        }

        return $this->campaignCache[$campaignKey];
    }

    /**
     * Get provider ID (create if needed)
     */
    protected function getProviderId($providerName, $providerExtension)
    {
        $cacheKey = $providerName.'|'.$providerExtension;

        if (! isset($this->providerCache[$cacheKey])) {
            // First try to find by name and extension
            $provider = Provider::where('name', $providerName)
                ->where('tenant_id', $this->tenantId)
                ->first();

            if (! $provider) {
                // Then try by extension if available
                if (! empty($providerExtension)) {
                    $provider = Provider::where('extension', $providerExtension)
                        ->where('tenant_id', $this->tenantId)
                        ->first();
                }

                // If still not found, create a new provider
                if (! $provider) {
                    try {
                        DB::beginTransaction();
                        $provider = Provider::create([
                            'tenant_id' => $this->tenantId,
                            'slug' => Str::slug($providerName),
                            'name' => $providerName,
                            'extension' => $providerExtension ?: null, // Use null if empty
                        ]);
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Error creating provider: '.$e->getMessage());
                        // Try to find it again in case it was created in another process
                        $provider = Provider::where('name', $providerName)
                            ->where('tenant_id', $this->tenantId)
                            ->first();

                        if (! $provider) {
                            throw $e;
                        }
                    }
                }
            }

            $this->providerCache[$cacheKey] = $provider->id;
        }

        return $this->providerCache[$cacheKey];
    }

    /**
     * Insert contacts in bulk with chunking for memory efficiency
     */
    protected function insertContacts(array $contacts)
    {
        try {
            // Use chunk-based inserts to prevent memory issues with large datasets
            DB::table('contacts')->insert($contacts);
        } catch (\Exception $e) {
            Log::error('Error inserting contacts: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Save a skipped phone number to the database
     */
    protected function saveSkippedNumber(string $phoneNumber, ?int $providerId, ?int $agentId, ?int $campaignId, string $skipReason, ?string $fileName, int $rowNumber, ?array $rawData = null)
    {
        try {
            SkippedNumber::create([
                'phone_number' => $phoneNumber,
                'provider_id' => $providerId,
                'agent_id' => $agentId,
                'campaign_id' => $campaignId,
                'tenant_id' => $this->tenantId,
                'batch_id' => $this->batchId,
                'file_name' => $fileName,
                'skip_reason' => $skipReason,
                'row_number' => $rowNumber,
                'raw_data' => $rawData,
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving skipped number: '.$e->getMessage());
            // Don't throw, just log - we don't want to interrupt the import process
        }
    }

    /**
     * Update the progress in the cache
     */
    protected function updateProgress(int $count)
    {
        Cache::put("agent-csv-import-{$this->batchId}-progress", $count, now()->addHours(1));
    }
}
