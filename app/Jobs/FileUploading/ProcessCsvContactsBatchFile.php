<?php

namespace App\Jobs\FileUploading;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\SkippedNumber;
use App\Services\LicenseService;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ProcessCsvContactsBatchFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    protected $batchId;

    protected $fileName;

    protected $tenant;

    protected $chunkSize = 1000;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, string $batchId, string $fileName, $tenant)
    {
        $this->filePath = $filePath;
        $this->batchId = $batchId;
        $this->fileName = $fileName;
        $this->tenant = $tenant;

        // Validate tenant ID
        if (empty($this->tenant->id) || ! is_numeric($this->tenant->id)) {
            Log::error('Invalid tenant ID in job construction: '.var_export($this->tenant->id, true));
        } else {
            Log::info("CSV job constructed for tenant ID: {$this->tenant->id}, batch: {$this->batchId}");
        }
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Starting CSV import for batch: {$this->batchId}, file: {$this->fileName}, tenant: {$this->tenant->id}");

        // Set tenant connection at the beginning
        TenantService::setConnection($this->tenant);

        // Verify the tenant actually exists
        $tenantExists = DB::table('tenants')->where('id', $this->tenant->id)->exists();
        if (! $tenantExists) {
            Log::error("Cannot process CSV import: Tenant ID {$this->tenant->id} does not exist");
            return;
        }

        if (! file_exists($this->filePath)) {
            Log::error("File not found: {$this->filePath}");
            return;
        }

        $file = fopen($this->filePath, 'r');
        if (! $file) {
            Log::error("Could not open file: {$this->filePath}");
            return;
        }

        // Read and validate the header
        $header = fgetcsv($file);
        if (! $header) {
            Log::error("Empty or invalid CSV file: {$this->filePath}");
            fclose($file);
            unlink($this->filePath);
            return;
        }

        // Trim header values to remove potential whitespace
        $header = array_map('trim', $header);

        Log::info('CSV headers: '.implode(', ', $header));

        // Check for required columns
        $requiredColumns = ['file_name', 'provider_name', 'provider_extension', 'phone_number', 'start_time', 'end_time'];
        $missingColumns = [];

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $header)) {
                $missingColumns[] = $column;
            }
        }

        if (! empty($missingColumns)) {
            $missingColumnsList = implode(', ', $missingColumns);
            Log::error("Missing required columns in CSV file: {$missingColumnsList}");
            fclose($file);
            unlink($this->filePath);
            return;
        }

        $contactsData = [];
        $processedCount = 0;
        $skippedCount = 0;
        $rowNumber = 1; // Header is row 0, data starts at row 1
        $campaignCache = []; // Cache to store campaign IDs by file_name+provider combination
        $providerCache = []; // Cache to store provider IDs by name+extension
        $fileToCampaignProvider = []; // Map to track which provider belongs to which file

        $licenseSevice = new LicenseService; // Create a single instance outside the loop

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty($row) || count(array_filter($row)) === 0) {
                Log::warning("Skipping empty row at line {$rowNumber}");
                $skippedCount++;
                continue;
            }

            // Check if row count matches header count
            if (count($row) !== count($header)) {
                Log::warning("Skipping malformed row at line {$rowNumber}. Expected ".count($header).
                             ' columns but got '.count($row).'. Row data: '.json_encode($row));

                // Track skipped malformed row
                try {
                    $partialData = array_combine(
                        array_slice($header, 0, min(count($header), count($row))),
                        array_slice($row, 0, min(count($header), count($row)))
                    );

                    $fileName = $partialData['file_name'] ?? null;
                    $providerName = $partialData['provider_name'] ?? null;
                    $providerExtension = $partialData['provider_extension'] ?? null;
                    $phoneNumber = $partialData['phone_number'] ?? 'unknown';

                    // Try to find provider ID if we have provider info
                    $providerId = null;
                    if ($providerName || $providerExtension) {
                        TenantService::setConnection($this->tenant);
                        $provider = Provider::where('tenant_id', $this->tenant->id)
                            ->where(function ($query) use ($providerName, $providerExtension) {
                                $query->where('name', $providerName)
                                    ->orWhere('extension', $providerExtension);
                            })
                            ->first();
                        if ($provider) {
                            $providerId = $provider->id;
                        }
                    }

                    // Try to find campaign ID if we have file name and provider info
                    $campaignId = null;
                    if ($fileName && $providerId) {
                        TenantService::setConnection($this->tenant);
                        $campaign = Campaign::where('tenant_id', $this->tenant->id)
                            ->where('name', $fileName)
                            ->where('provider_id', $providerId)
                            ->first();
                        if ($campaign) {
                            $campaignId = $campaign->id;
                        }
                    }

                    $this->saveSkippedNumber(
                        $phoneNumber,
                        $providerId,
                        $campaignId,
                        "Skipping malformed row at line {$rowNumber}",
                        $fileName,
                        $rowNumber,
                        $partialData
                    );
                } catch (\Exception $e) {
                    Log::error('Error saving skipped number for malformed row: '.$e->getMessage());
                }

                $skippedCount++;
                continue;
            }

            // Trim values to remove potential whitespace
            $row = array_map('trim', $row);

            try {
                $data = array_combine($header, $row);

                // Validate required fields
                if (empty($data['file_name']) || empty($data['provider_name']) ||
                    empty($data['provider_extension']) || empty($data['phone_number'])) {
                    Log::warning("Skipping row {$rowNumber} with missing required fields: ".json_encode($data));

                    // Track skipped row with missing fields
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        null,
                        null,
                        'missing_required_fields',
                        $data['file_name'] ?? null,
                        $rowNumber,
                        $data
                    );

                    $skippedCount++;
                    continue;
                }

                $fileName = $data['file_name'];
                $providerName = $data['provider_name'];
                $providerExtension = $data['provider_extension'];
                $providerKey = $providerName.'|'.$providerExtension;

                // Associate this file with this provider
                $fileToCampaignProvider[$fileName] = $providerKey;

                // Get or create provider (using cache to reduce DB queries)
                if (! isset($providerCache[$providerKey])) {
                    try {
                        TenantService::setConnection($this->tenant);
                        
                        // First try to find the existing provider with tenant scope
                        $provider = Provider::where('tenant_id', $this->tenant->id)
                            ->where('name', $providerName)
                            ->where('extension', $providerExtension)
                            ->first();

                        if (! $provider) {
                            // Generate a base slug
                            $baseSlug = Str::slug($providerName.'-'.$providerExtension);

                            // If the base slug is empty (e.g., from non-Latin characters), use a fallback
                            if (empty($baseSlug)) {
                                $baseSlug = 'provider-'.substr(md5($providerName.$providerExtension), 0, 8);
                            }

                            TenantService::setConnection($this->tenant);
                            // Check if the slug already exists within this tenant
                            $slugExists = Provider::where('tenant_id', $this->tenant->id)
                                ->where('slug', $baseSlug)
                                ->exists();
                            $counter = 0;
                            $slug = $baseSlug;

                            // If the slug exists, append a counter until we find a unique slug
                            while ($slugExists) {
                                $counter++;
                                $slug = $baseSlug.'-'.$counter;
                                TenantService::setConnection($this->tenant);
                                $slugExists = Provider::where('tenant_id', $this->tenant->id)
                                    ->where('slug', $slug)
                                    ->exists();
                            }

                            // Check if License is valid
                            if ($licenseSevice->validProvidersCount($this->tenant->id)) {
                                // Create the provider with the unique slug and ensure tenant_id is set
                                TenantService::setConnection($this->tenant);
                                $provider = Provider::create([
                                    'tenant_id' => $this->tenant->id,
                                    'slug' => $slug,
                                    'name' => $providerName,
                                    'extension' => $providerExtension,
                                ]);

                                Log::info("Tenant {$this->tenant->id}: Created new provider: {$providerName} ({$providerExtension}) with slug: {$slug}, ID: {$provider->id}");
                            } else {
                                Log::error("Tenant {$this->tenant->id}: License validation failed for provider creation. Cannot process CSV import.");
                                break; // Exit the loop if license validation fails
                            }
                        } else {
                            Log::info("Tenant {$this->tenant->id}: Found existing provider: {$providerName} ({$providerExtension}) with slug: {$provider->slug}, ID: {$provider->id}");
                        }

                        $providerCache[$providerKey] = $provider->id;
                    } catch (\Exception $e) {
                        Log::error("Tenant {$this->tenant->id}: Error processing provider {$providerName} ({$providerExtension}): ".$e->getMessage());
                        throw $e;
                    }
                }
                $providerId = $providerCache[$providerKey];

                // Get or create campaign based on the file_name in the current row
                $campaignKey = $fileName.'|'.$providerKey;
                if (! isset($campaignCache[$campaignKey])) {
                    try {
                        // Generate a unique slug using multiple uniqueness factors
                        $uniqueId = Str::uuid()->toString();
                        $timestamp = now()->format('YmdHis');
                        $microseconds = sprintf('%06d', now()->microsecond);
                        $tenantPrefix = substr(md5($this->tenant->id), 0, 6);

                        // Create base slug from filename and provider
                        $fileNameSlug = Str::slug($fileName);

                        // If the filename produces an empty slug (e.g., from non-Latin characters), use a fallback
                        if (empty($fileNameSlug)) {
                            $fileNameSlug = 'campaign-'.substr(md5($fileName), 0, 8);
                        }

                        // Provider extension part
                        $providerPart = Str::slug($providerExtension);
                        if (empty($providerPart)) {
                            $providerPart = 'p'.substr(md5($providerExtension), 0, 4);
                        }

                        // Combine all elements to create a unique slug
                        $uniqueSlug = $tenantPrefix.'_'.
                                      $fileNameSlug.'_'.
                                      $providerPart.'_'.
                                      $timestamp.
                                      $microseconds.'_'.
                                      substr($uniqueId, 0, 8);

                        // Ensure the slug is not too long (DB column might have limits)
                        if (strlen($uniqueSlug) > 190) {
                            $uniqueSlug = $tenantPrefix.'_'.
                                          substr(md5($fileNameSlug), 0, 8).'_'.
                                          substr(md5($providerPart), 0, 6).'_'.
                                          $timestamp.
                                          $microseconds.'_'.
                                          substr($uniqueId, 0, 8);
                        }

                        // Double-check uniqueness just to be absolutely certain
                        $isUnique = false;
                        $attempts = 0;
                        $finalSlug = $uniqueSlug;

                        while (! $isUnique && $attempts < 10) {
                            TenantService::setConnection($this->tenant);
                            $exists = Campaign::where('slug', $finalSlug)->exists();

                            if (! $exists) {
                                $isUnique = true;
                            } else {
                                // On the rare chance of collision, add more randomness
                                $attempts++;
                                $finalSlug = $uniqueSlug.'_'.Str::random(8);
                            }
                        }

                        // If we still have a collision after 10 attempts (extremely unlikely),
                        // generate a completely random slug
                        if (! $isUnique) {
                            $finalSlug = 'campaign_'.$tenantPrefix.'_'.
                                         $timestamp.$microseconds.'_'.
                                         Str::random(16);
                        }

                        TenantService::setConnection($this->tenant);
                        $tenant_auto_call = Setting::where('tenant_id', $this->tenant->id)->first();

                        // Lock the row during creation to prevent race conditions
                        $campaign = DB::transaction(function () use ($finalSlug, $fileName, $providerId, $data, $licenseSevice, $tenant_auto_call) {
                            TenantService::setConnection($this->tenant);
                            
                            // Prepare campaign data
                            $campaignData = [
                                'tenant_id' => $this->tenant->id,
                                'provider_id' => $providerId,
                                'slug' => $finalSlug,
                                'name' => $fileName,
                                'start_time' => $data['start_time'] ?? now()->format('Y-m-d H:i:s'),
                                'end_time' => $data['end_time'] ?? now()->addHours(1)->format('Y-m-d H:i:s'),
                                'campaign_type' => 'dialer',
                            ];

                            // Check if batch_id column exists before adding it
                            TenantService::setConnection($this->tenant);
                            if (Schema::hasColumn('campaigns', 'batch_id')) {
                                $campaignData['batch_id'] = $this->batchId;
                            }

                            if ($licenseSevice->validCampaignsCount($this->tenant->id)) {
                                // Set 'allow' only if auto_call is false
                                if ($tenant_auto_call && $tenant_auto_call->auto_call == false) {
                                    $campaignData['allow'] = false;
                                }
                                
                                // Create campaign with a lock to prevent concurrent creation
                                TenantService::setConnection($this->tenant);
                                $campaign = Campaign::create($campaignData);

                                return $campaign;
                            } else {
                                Log::error("Tenant {$this->tenant->id}: License validation failed for campaign creation. Cannot process CSV import.");
                                return null;
                            }
                        }, 5); // 5 retries if deadlock occurs

                        if (!$campaign) {
                            Log::error("Tenant {$this->tenant->id}: Failed to create campaign due to license restrictions.");
                            break; // Exit the loop if campaign creation fails
                        }

                        Log::info("Tenant {$this->tenant->id}: Created new campaign: {$fileName} with slug: {$finalSlug}, ID: {$campaign->id}, batch: {$this->batchId}");
                        $campaignCache[$campaignKey] = $campaign->id;
                    } catch (\Exception $e) {
                        Log::error("Tenant {$this->tenant->id}: Error creating campaign {$fileName}: ".$e->getMessage());
                        throw $e; // Re-throw to ensure the row is skipped properly
                    }
                }

                $campaignId = $campaignCache[$campaignKey];

                // Generate slug for the contact
                $contactSlug = Str::slug($fileName.'-'.$data['phone_number'].'-'.$campaignId.'-'.Str::random(8));

                // Clean and standardize the phone number by removing all non-digit characters
                $cleanPhoneNumber = preg_replace('/[^0-9]/', '', $data['phone_number']);

                // Skip if phone number is empty after cleaning
                if (empty($cleanPhoneNumber)) {
                    Log::warning("Tenant {$this->tenant->id}: Skipping row {$rowNumber} with invalid phone number: {$data['phone_number']}");
                    // Track skipped number
                    $this->saveSkippedNumber(
                        $data['phone_number'],
                        $providerId,
                        $campaignId,
                        'invalid_phone_number',
                        $fileName,
                        $rowNumber,
                        $data
                    );
                    $skippedCount++;
                    continue;
                }

                // Add to batch for insertion, ensuring data is associated with correct campaign
                $contactsData[] = [
                    'phone_number' => $data['phone_number'], // Keep original format
                    'slug' => $contactSlug,
                    'campaign_id' => $campaignId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($contactsData) >= $this->chunkSize) {
                    if ($licenseSevice->validContactsPerCampaignCount($this->tenant->id)) {
                        $this->insertContacts($contactsData);
                        $processedCount += count($contactsData);
                        $this->updateProgress($processedCount);
                        $contactsData = [];
                    } else {
                        Log::warning("Tenant {$this->tenant->id}: License contact limit reached, stopping import");
                        break;
                    }
                }

            } catch (\Exception $e) {
                Log::error("Tenant {$this->tenant->id}: Error processing row {$rowNumber}: ".$e->getMessage());

                // Track skipped row with processing error
                try {
                    $this->saveSkippedNumber(
                        $data['phone_number'] ?? 'unknown',
                        $providerId ?? null,
                        $campaignId ?? null,
                        'processing_error: '.class_basename($e),
                        $fileName ?? null,
                        $rowNumber,
                        $data ?? []
                    );
                } catch (\Exception $saveEx) {
                    Log::error("Tenant {$this->tenant->id}: Error saving skipped number: ".$saveEx->getMessage());
                }

                $skippedCount++;
            }
        }

        // Process any remaining contacts
        if (!empty($contactsData)) {
            if ($licenseSevice->validContactsPerCampaignCount($this->tenant->id)) {
                $this->insertContacts($contactsData);
                $processedCount += count($contactsData);
                $this->updateProgress($processedCount);
            } else {
                Log::warning("Tenant {$this->tenant->id}: License contact limit reached, some contacts not processed");
            }
        }

        fclose($file);
        unlink($this->filePath);

        Log::info("Tenant {$this->tenant->id}: CSV import completed for batch {$this->batchId}. Processed: {$processedCount}, Skipped: {$skippedCount}");

        // Double check that records were actually created, but handle the case where batch_id column might not exist
        try {
            TenantService::setConnection($this->tenant);
            if (Schema::hasColumn('campaigns', 'batch_id')) {
                $campaignCount = Campaign::where('tenant_id', $this->tenant->id)
                    ->where('batch_id', $this->batchId)
                    ->count();

                TenantService::setConnection($this->tenant);
                $contactCount = DB::table('contacts')
                    ->join('campaigns', 'contacts.campaign_id', '=', 'campaigns.id')
                    ->where('campaigns.tenant_id', $this->tenant->id)
                    ->where('campaigns.batch_id', $this->batchId)
                    ->count();

                Log::info("Tenant {$this->tenant->id}: Final verification - Campaigns created: {$campaignCount}, Contacts created: {$contactCount}");
            } else {
                // If batch_id column doesn't exist, just report overall counts
                TenantService::setConnection($this->tenant);
                $recentCampaigns = Campaign::where('tenant_id', $this->tenant->id)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->count();

                TenantService::setConnection($this->tenant);
                $contactCount = DB::table('contacts')
                    ->join('campaigns', 'contacts.campaign_id', '=', 'campaigns.id')
                    ->where('campaigns.tenant_id', $this->tenant->id)
                    ->where('campaigns.created_at', '>=', now()->subMinutes(5))
                    ->count();

                Log::info("Tenant {$this->tenant->id}: Final verification - Recent campaigns created: {$recentCampaigns}, Recent contacts created: {$contactCount}");
            }
        } catch (\Exception $e) {
            // Just log the error but don't fail the job since data has been processed
            Log::error("Tenant {$this->tenant->id}: Error during final verification: ".$e->getMessage());
        }
    }

    /**
     * Insert contacts in bulk with transaction support and tenant validation
     */
    protected function insertContacts(array $contacts)
    {
        try {
           
            DB::beginTransaction();

            // Add a safety check to verify contacts are being created for the right tenant
            $campaignIds = array_unique(array_column($contacts, 'campaign_id'));

            // Verify campaigns belong to the correct tenant
            $validCampaigns = Campaign::whereIn('id', $campaignIds)
                ->where('tenant_id', $this->tenant->id)
                ->count();

            if ($validCampaigns !== count($campaignIds)) {
                Log::error("Tenant {$this->tenant->id}: Tenant mismatch detected during contact insert. Expected tenant: {$this->tenant->id}, valid campaigns: {$validCampaigns}");
                throw new \Exception('Tenant security validation failed');
            }
            TenantService::setConnection($this->tenant);
            Contact::insert($contacts);
            DB::commit();

            Log::info("Tenant {$this->tenant->id}: Successfully inserted ".count($contacts).' contacts');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Tenant {$this->tenant->id}: Error inserting contacts: ".$e->getMessage());
            throw $e;
        }
    }

    /**
     * Save a skipped phone number to the database with improved validation and error handling
     */
    protected function saveSkippedNumber(string $phoneNumber, ?int $providerId, ?int $campaignId, string $skipReason, ?string $fileName, int $rowNumber, ?array $rawData = null)
    {
        try {
            TenantService::setConnection($this->tenant);
            
            // Clean phone number
            $cleanPhoneNumber = preg_replace('/[^0-9+\-() ]/', '', $phoneNumber);
            
            // Validate provider exists if ID is provided
            if ($providerId) {
                $providerExists = Provider::where('id', $providerId)
                    ->where('tenant_id', $this->tenant->id)
                    ->exists();
                
                if (!$providerExists) {
                    Log::warning("Invalid provider_id {$providerId} for tenant {$this->tenant->id}, setting to null");
                    $providerId = null;
                }
            }

            // Validate campaign exists if ID is provided
            if ($campaignId) {
                $campaignExists = Campaign::where('id', $campaignId)
                    ->where('tenant_id', $this->tenant->id)
                    ->exists();
                
                if (!$campaignExists) {
                    Log::warning("Invalid campaign_id {$campaignId} for tenant {$this->tenant->id}, setting to null");
                    $campaignId = null;
                }
            }

            // Create skipped number record with validation
            SkippedNumber::create([
                'phone_number' => $cleanPhoneNumber,
                'provider_id' => $providerId,
                'campaign_id' => $campaignId,
                'tenant_id' => $this->tenant->id,
                'batch_id' => $this->batchId,
                'file_name' => $fileName ? substr($fileName, 0, 255) : null,
                'skip_reason' => substr($skipReason, 0, 500),
                'row_number' => $rowNumber,
                'raw_data' => $rawData ? json_encode($rawData) : null,
            ]);

            Log::info("Successfully saved skipped number record for phone: {$cleanPhoneNumber}, tenant: {$this->tenant->id}, reason: {$skipReason}");
        } catch (\Exception $e) {
            Log::error("Error saving skipped number for tenant {$this->tenant->id}: " . $e->getMessage(), [
                'phone_number' => $phoneNumber,
                'provider_id' => $providerId,
                'campaign_id' => $campaignId,
                'file_name' => $fileName,
                'row_number' => $rowNumber,
                'exception' => $e
            ]);
        }
    }

    /**
     * Update the progress in the cache
     */
    protected function updateProgress(int $count)
    {
        Cache::put("csv-import-{$this->batchId}-progress", $count, now()->addHours(1));
    }
}