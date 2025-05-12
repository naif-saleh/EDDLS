<?php

namespace App\Livewire\Systems\Campaign;

use App\Models\Campaign;
use App\Models\Provider;
use App\Models\Tenant;
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
        $this->campaign->where('id', $campaignId)->update([
            'name' => $this->campaignName,
            'start_time' => $this->campaignStart,
            'end_time' => $this->campaignEnd,
            'campaign_type' => $this->campaignType,
            'allow' => $this->campaignStatus,
        ]);
        Toaster::success('Campaign '.$this->campaignName. ' Updated Successfully');
    }

    public function render()
    {
        return view('livewire.systems.campaign.dialer-campaign-update');
    }
}
