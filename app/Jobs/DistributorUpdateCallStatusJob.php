<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\CallLog;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\DistributorCallsReport;
use App\Models\Provider;
use App\Services\LicenseService;
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

class DistributorUpdateCallStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;
    
   /**
     * The tenant ID
     * 
     * @var int
     */
    protected $tenant;
    
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
     * @param int $tenant
     * @param string $callId
     * @param string $callStatus
     * @param array $callsData
     * @return void
     */
    public function __construct($tenant, $callId, $callStatus, $callsData)
    {
        $this->tenant = $tenant;
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
            $threeCxService = new ThreeCXIntegrationService($this->tenant);
            $this->updateCallRecord();
        } catch (\Exception $e) {
            Log::error("Error in UpdateCallStatusJob: {$e->getMessage()}", [
                'tenant_id' => $this->tenant,
                'call_id' => $this->callId,
                'exception' => $e,
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
     * @return mixed
     */
    protected function updateCallRecord()
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
            $establishedAt = Carbon::parse($callData['EstablishedAt']);
            $serverNow = Carbon::parse($callData['ServerNow']);
            $currentDuration = $establishedAt->diff($serverNow)->format('%H:%I:%S');
            
            TenantService::setConnection($this->tenant);
            // Retrieve existing record to preserve any existing durations
            $existingRecord = DistributorCallsReport::on('tenant')->where('call_id', $this->callId)->first();

            if ($existingRecord) {
                // Update durations based on current status
                switch ($this->callStatus) {
                    case 'Talking':
                        $talking_duration = $currentDuration;
                        $dialing_duration = $existingRecord->dialing_duration;
                        break;
                    case 'Routing':
                        $dialing_duration = $currentDuration;
                        $talking_duration = $existingRecord->talking_duration;
                        break;
                    default:
                        $talking_duration = $existingRecord->talking_duration;
                        $dialing_duration = $existingRecord->dialing_duration;
                }
            }
        } else {
            TenantService::setConnection($this->tenant);
            // If updating without call data, preserve existing durations
            $existingRecord = DistributorCallsReport::on('tenant')->where('call_id', $this->callId)->first();

            if ($existingRecord) {
                $talking_duration = $existingRecord->talking_duration ?? null;
                $dialing_duration = $existingRecord->dialing_duration ?? null;
            }
        }

        return DB::transaction(function () use ($talking_duration, $dialing_duration, $currentDuration) {
            TenantService::setConnection($this->tenant);
            $report = DistributorCallsReport::on('tenant')->where('call_id', $this->callId)->update([
                'call_status' => $this->callStatus,
                'talking_duration' => $talking_duration,
                'dialing_duration' => $dialing_duration,
            ]);

            Contact::where('call_id', $this->callId)->update(['status' => $this->callStatus]);

            Log::info("UpdateCallStatusJob ☎️✅ Call status updated for call_id: {$this->callId}, " .
                "Status: {$this->callStatus}, " .
                "Duration: " . ($currentDuration ?? 'N/A'));

            return $report;
        });
    }
}