<?php

namespace App\Livewire\Systems\Campaign;

use App\Models\Campaign;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\SystemLogService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class DialerCampaignUpdate extends Component
{
    // Form fields
    public $campaignName;

    public $campaignStart;

    public $campaignEnd;

    public $campaignType = 'dialer'; // Default value

    public $campaignStatus;

    public $provider = '';

    public $tenant = '';

    public $campaign = '';

    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    public function mount(Provider $provider, Tenant $tenant, Campaign $campaign)
    {
        $this->provider = $provider;
        $this->tenant = $tenant;

        $this->campaign = Campaign::find($campaign->id);
        
        $this->fill([
            'campaignName' => $this->campaign->name,
            'campaignStart' => $this->campaign->start_time,
            'campaignEnd' => $this->campaign->end_time,
            'campaignType' => $this->campaign->campaign_type,
            'campaignStatus' => $this->campaign->allow,
        ]);

        // Log campaign edit initiation
        $this->getSystemLogService()->log(
            logType: 'ui_action',
            action: 'edit_campaign_initiated',
            model: $this->campaign,
            description: "Initiated editing campaign: {$this->campaign->name}",
            metadata: [
                'provider_id' => $this->provider->id
            ]
        );
    }

    // Validation rules
    protected $rules = [
        'campaignName' => 'required|string|min:3|max:255',
        'campaignStart' => 'required|date|after_or_equal:now',
        'campaignEnd' => 'required|date|after:campaignStart',
        'campaignType' => 'required|in:dialer,distributor',
        'campaignStatus' => 'required|in:active,inactive',
    ];

    public function updateCampaign($campaignId)
    {
        try {
            // Store original values for logging
            $originalData = $this->campaign->where('id', $campaignId)->first()->toArray();

            // Perform update
            $this->campaign->where('id', $campaignId)->update([
                'name' => $this->campaignName,
                'start_time' => $this->campaignStart,
                'end_time' => $this->campaignEnd,
                'campaign_type' => $this->campaignType,
                'allow' => $this->campaignStatus,
            ]);

            // Get updated campaign for logging
            $updatedCampaign = Campaign::find($campaignId);

            // Log the update
            $this->getSystemLogService()->logUpdate(
                model: $updatedCampaign,
                originalAttributes: [
                    'name' => $originalData['name'],
                    'start_time' => $originalData['start_time'],
                    'end_time' => $originalData['end_time'],
                    'campaign_type' => $originalData['campaign_type'],
                    'allow' => $originalData['allow'],
                ],
                description: "Updated campaign: {$this->campaignName}",
                metadata: [
                    'provider_id' => $this->provider->id,
                    'updated_fields' => array_keys(array_diff_assoc([
                        'name' => $this->campaignName,
                        'start_time' => $this->campaignStart,
                        'end_time' => $this->campaignEnd,
                        'campaign_type' => $this->campaignType,
                        'allow' => $this->campaignStatus,
                    ], [
                        'name' => $originalData['name'],
                        'start_time' => $originalData['start_time'],
                        'end_time' => $originalData['end_time'],
                        'campaign_type' => $originalData['campaign_type'],
                        'allow' => $originalData['allow'],
                    ]))
                ]
            );

            Toaster::success('Campaign '.$this->campaignName. ' Updated Successfully');
        } catch (\Exception $e) {
            // Log error
            $this->getSystemLogService()->log(
                logType: 'error',
                action: 'campaign_update_failed',
                model: $this->campaign,
                description: "Failed to update campaign: {$e->getMessage()}",
                metadata: [
                    'campaign_id' => $campaignId,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );

            Toaster::error('Error updating campaign: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.systems.campaign.dialer-campaign-update');
    }
}
