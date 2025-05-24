<?php

namespace App\Jobs;

use App\Models\CallLog;
use App\Models\Contact;
use App\Models\DialerCallsReport;
use App\Models\Tenant;
use App\Services\TenantService;
use App\Services\ThreeCXIntegrationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DialerUpdateCallStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;
    
    /**
     * The tenant ID
     * 
     * @var int
     */
    protected $tenantId;
    
    /**
     * Call ID to update
     * 
     * @var string
     */
    protected $callId;
    
    /**
     * Current call status
     * 
     * @var string
     */
    protected $callStatus;
    
    /**
     * All call data for reference
     * 
     * @var array
     */
    protected $callsData;

    /**
     * Create a new job instance.
     *
     * @param int $tenantId
     * @param string $callId
     * @param string $callStatus
     * @param array $callsData
     * @return void
     */
    public function __construct($tenantId, $callId, $callStatus, $callsData)
    {
        $this->tenantId = $tenantId;
        $this->callId = $callId;
        $this->callStatus = $callStatus;
        $this->callsData = $callsData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // First, get and validate the tenant
            $tenant = Tenant::find($this->tenantId);
            if (!$tenant) {
                Log::error("Tenant not found for UpdateCallStatusJob", [
                    'tenant_id' => $this->tenantId,
                    'call_id' => $this->callId,
                ]);
                return;
            }

            // Set up tenant connection
            TenantService::setConnection($tenant);
            
            // Test the connection
            DB::connection('tenant')->select('SELECT 1');
            
            Log::info("Processing call status update", [
                'tenant_id' => $this->tenantId,
                'call_id' => $this->callId,
                'status' => $this->callStatus,
            ]);

            $this->updateCallRecord($tenant);
            
        } catch (\Exception $e) {
            Log::error("Error in DialerUpdateCallStatusJob: {$e->getMessage()}", [
                'tenant_id' => $this->tenantId,
                'call_id' => $this->callId,
                'call_status' => $this->callStatus,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Rethrow if we want the job to retry
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }
    
    /**
     * Update call record in database with consistent format
     * 
     * @param Tenant $tenant
     * @return mixed
     */
    protected function updateCallRecord(Tenant $tenant)
    {
        $talking_duration = null;
        $dialing_duration = null;
        $currentDuration = null;
        
        // Find the specific call data in the array
        $callData = null;
        foreach ($this->callsData as $call) {
            if (isset($call['Id']) && $call['Id'] == $this->callId) {
                $callData = $call;
                break;
            }
        }

        // Calculate durations if call data is found
        if ($callData && isset($callData['EstablishedAt']) && isset($callData['ServerNow'])) {
            try {
                $establishedAt = Carbon::parse($callData['EstablishedAt']);
                $serverNow = Carbon::parse($callData['ServerNow']);
                $currentDuration = $establishedAt->diff($serverNow)->format('%H:%I:%S');
                
                Log::info("Duration calculated", [
                    'call_id' => $this->callId,
                    'established_at' => $callData['EstablishedAt'],
                    'server_now' => $callData['ServerNow'],
                    'current_duration' => $currentDuration,
                ]);
                
            } catch (\Exception $e) {
                Log::warning("Failed to calculate duration", [
                    'call_id' => $this->callId,
                    'established_at' => $callData['EstablishedAt'] ?? 'missing',
                    'server_now' => $callData['ServerNow'] ?? 'missing',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return DB::connection('tenant')->transaction(function () use ($talking_duration, $dialing_duration, $currentDuration, $tenant) {
            // Ensure tenant connection is still active within the transaction
            TenantService::setConnection($tenant);
            
            // Get existing record to preserve durations
            $existingRecord = DialerCallsReport::on('tenant')->where('call_id', $this->callId)->first();

            if ($existingRecord) {
                // Update durations based on current status
                switch ($this->callStatus) {
                    case 'Talking':
                        $talking_duration = $currentDuration ?? $existingRecord->talking_duration;
                        $dialing_duration = $existingRecord->dialing_duration;
                        break;
                    case 'Routing':
                    case 'Ringing':
                        $dialing_duration = $currentDuration ?? $existingRecord->dialing_duration;
                        $talking_duration = $existingRecord->talking_duration;
                        break;
                    case 'Completed':
                    case 'Ended':
                    case 'Disconnected':
                        // Preserve existing durations for completed calls
                        $talking_duration = $existingRecord->talking_duration;
                        $dialing_duration = $existingRecord->dialing_duration;
                        break;
                    default:
                        $talking_duration = $existingRecord->talking_duration;
                        $dialing_duration = $existingRecord->dialing_duration;
                }

                // Update the report
                $updated = DialerCallsReport::on('tenant')->where('call_id', $this->callId)->update([
                    'call_status' => $this->callStatus,
                    'talking_duration' => $talking_duration,
                    'dialing_duration' => $dialing_duration,
                    'updated_at' => now(),
                ]);

                if ($updated) {
                    Log::info("DialerCallsReport updated successfully", [
                        'call_id' => $this->callId,
                        'status' => $this->callStatus,
                        'talking_duration' => $talking_duration,
                        'dialing_duration' => $dialing_duration,
                    ]);
                } else {
                    Log::warning("DialerCallsReport update returned 0 affected rows", [
                        'call_id' => $this->callId,
                        'status' => $this->callStatus,
                    ]);
                }

                // Update related contact status if needed
                try {
                    $contactUpdated = Contact::on('tenant')->where('call_id', $this->callId)->update([
                        'status' => $this->mapCallStatusToContactStatus($this->callStatus),
                        'updated_at' => now(),
                    ]);

                    if ($contactUpdated > 0) {
                        Log::info("Contact status updated", [
                            'call_id' => $this->callId,
                            'contact_status' => $this->mapCallStatusToContactStatus($this->callStatus),
                            'contacts_updated' => $contactUpdated,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to update contact status", [
                        'call_id' => $this->callId,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info("UpdateCallStatusJob ☎️✅ Call status updated for call_id: {$this->callId}, " .
                    "Status: {$this->callStatus}, " .
                    "Duration: " . ($currentDuration ?? 'N/A'));

                return $updated;
                
            } else {
                Log::warning("No existing DialerCallsReport found for call_id: {$this->callId}");
                return false;
            }
        });
    }

    /**
     * Map call status to appropriate contact status
     * 
     * @param string $callStatus
     * @return string
     */
    protected function mapCallStatusToContactStatus($callStatus)
    {
        switch ($callStatus) {
            case 'Talking':
                return 'talking';
            case 'Routing':
            case 'Ringing':
                return 'calling';
            case 'Completed':
            case 'Ended':
                return 'completed';
            case 'Disconnected':
            case 'Failed':
                return 'failed';
            default:
                return 'calling';
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("DialerUpdateCallStatusJob failed permanently", [
            'tenant_id' => $this->tenantId,
            'call_id' => $this->callId,
            'call_status' => $this->callStatus,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}