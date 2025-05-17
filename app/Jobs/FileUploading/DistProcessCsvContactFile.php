<?php

namespace App\Jobs\FileUploading;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use App\Models\SkippedNumber;
use App\Services\LicenseService;
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

    // Cache license validation results
    protected $providerLicenseValid = null;
    protected $campaignLicenseValid = null;

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
        $licenseService = new LicenseService;
        Log::info("Starting agent-based CSV import for batch: {$this->batchId}, file: {$this->fileName}");

        // Validate licenses upfront to avoid repeated checks during processing
        $this->providerLicenseValid = $licenseService->validProvidersCount($this->tenantId);
        $this->campaignLicenseValid = $licenseService->validCampaignsCount($this->tenantId);

        // Log license validation results
        if (!$this->providerLicenseValid) {
            Log::error("Tenant {$this->tenantId}: License validation failed for providers. Cannot process CSV import.");
        }

        if (!$this->campaignLicenseValid) {
            Log::error("Tenant {$this->tenantId}: License validation failed for campaigns. Cannot process CSV import.");
        }

        // Exit early if either license check fails
        if (!$this->providerLicenseValid || !$this->campaignLicenseValid) {
            if (file_exists($this->filePath)) {
                unlink($this->filePath);
            }
            return;
        }

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
            foreach ($this->getChunkedData($header, $licenseService) as $chunk) {
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
    protected function getChunkedData(array $header, $licenseService): Generator
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
                        $partialData['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        null,
                        $campaignId ?? null,
                        "Skipping malformed row at line {$rowNumber} mobile ",
                        $partialData['file_name'] ?? null,
                        $rowNumber,
                        $partialData ?? []
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
                        null,
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
                        null,
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
                Log::warning("Provider extension is missing row at line {$rowNumber} mobile ".$data['phone_number']);
                $skippedInChunk++;
                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        null,
                        $campaignId ?? null,
                        "Provider extension is missing row at line {$rowNumber} mobile ".$data['phone_number'],
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
                        null,
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
                        null,
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

            // Skip if campaign couldn't be created due to license issues
            if ($campaignId === null) {
                $skippedInChunk++;
                continue;
            }

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
            // If campaign license is not valid, return null
            if (!$this->campaignLicenseValid) {
                return null;
            }

            // Get provider first (or create it)
            $providerId = $this->getProviderId($providerName, $providerExtension);

            // If provider creation failed, return null
            if ($providerId === null) {
                return null;
            }

            // Use shorter transaction with retry logic to reduce lock contention
            $maxRetries = 3;
            $retryCount = 0;
            $campaign = null;

            while ($retryCount < $maxRetries) {
                try {
                    DB::beginTransaction();

                    // First try to find the campaign
                    $campaign = Campaign::where('name', $fileName)
                        ->where('agent_id', $agentId)
                        ->first();

                    if (!$campaign) {
                        $campaign = Campaign::create([
                            'tenant_id' => $this->tenantId,
                            'provider_id' => $providerId,
                            'agent_id' => $agentId,
                            'name' => $fileName,
                            'slug' => Str::slug($fileName.'-'.$agentId),
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'campaign_type' => 'distributor',
                        ]);
                    }

                    DB::commit();
                    $this->campaignCache[$campaignKey] = $campaign->id;
                    break;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $retryCount++;

                    if ($retryCount >= $maxRetries) {
                        Log::error('Failed to create/get campaign after multiple attempts: ' . $e->getMessage());
                        return null;
                    }

                    // Exponential backoff
                    usleep(($retryCount * 100) * 1000);

                    // Try to fetch after exception in case another process created it
                    try {
                        $campaign = Campaign::where('name', $fileName)
                            ->where('agent_id', $agentId)
                            ->first();

                        if ($campaign) {
                            $this->campaignCache[$campaignKey] = $campaign->id;
                            break;
                        }
                    } catch (\Exception $fetchEx) {
                        // Continue to retry
                    }
                }
            }
        }

        return $this->campaignCache[$campaignKey] ?? null;
    }

    /**
     * Get provider ID (create if needed)
     */
    protected function getProviderId($providerName, $providerExtension)
    {
        $cacheKey = $providerName.'|'.$providerExtension;

        if (! isset($this->providerCache[$cacheKey])) {
            // If provider license is not valid, return null
            if (!$this->providerLicenseValid) {
                return null;
            }

            // First try to find by name and extension
            $provider = Provider::where('name', $providerName)
                ->where('tenant_id', $this->tenantId)
                ->first();

            if (! $provider && ! empty($providerExtension)) {
                $provider = Provider::where('extension', $providerExtension)
                    ->where('tenant_id', $this->tenantId)
                    ->first();
            }

            if (! $provider) {
                // Use shorter transaction with retry logic to reduce lock contention
                $maxRetries = 3;
                $retryCount = 0;

                while ($retryCount < $maxRetries) {
                    try {
                        DB::beginTransaction();
                        $slug = Str::slug($providerName);
                        $provider = Provider::create([
                            'tenant_id' => $this->tenantId,
                            'slug' => $slug,
                            'name' => $providerName,
                            'extension' => $providerExtension ?: null,
                        ]);
                        DB::commit();

                        Log::info("Tenant {$this->tenantId}: Created new provider: {$providerName} ({$providerExtension}) with slug: {$slug}, ID: {$provider->id}");
                        break;
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $retryCount++;

                        if ($retryCount >= $maxRetries) {
                            Log::error('Failed to create provider after multiple attempts: ' . $e->getMessage());
                            return null;
                        }

                        // Exponential backoff
                        usleep(($retryCount * 100) * 1000);

                        // Try to fetch after exception in case another process created it
                        try {
                            $provider = Provider::where('name', $providerName)
                                ->where('tenant_id', $this->tenantId)
                                ->first();

                            if ($provider) {
                                break;
                            }
                        } catch (\Exception $fetchEx) {
                            // Continue to retry
                        }
                    }
                }
            }

            if ($provider) {
                $this->providerCache[$cacheKey] = $provider->id;
            } else {
                return null;
            }
        }

        return $this->providerCache[$cacheKey] ?? null;
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
