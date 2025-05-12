<?php

namespace App\Livewire\Systems\Campaign;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;


class CampaignChart extends Component
{
    /**
     * Get campaign data by type
     *
     * @return array
     */
    protected function getCampaignTypeData()
    {
        $tenantId = Auth::user()->tenant_id;

        // Get campaign counts by type
        $dialerCampaigns = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'dialer')
            ->count();
            // dd($dialerCampaigns);

        $distributorCampaigns = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'distributor')
            ->count();

        // Get campaign status data for each type
        $dialerActive = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'dialer')
            ->where('allow', 1)
            ->count();

        $dialerInactive = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'dialer')
            ->where('allow', 0)
            ->count();

        $distributorActive = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'distributor')
            ->where('allow', 1)
            ->count();

        $distributorInactive = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'distributor')
            ->where('allow', 0)
            ->count();

        // Calculate completion rates
        $dialerCompleted = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'dialer')
            ->whereHas('contacts', function($query) {
                $query->where('status', '!=', 'new');
            })
            ->count();

        $distributorCompleted = Campaign::where('tenant_id', $tenantId)
            ->where('campaign_type', 'distributor')
            ->whereHas('contacts', function($query) {
                $query->where('status', '!=', 'new');
            })
            ->count();

        $dialerCompletionRate = $dialerCampaigns > 0 ? round(($dialerCompleted / $dialerCampaigns) * 100) : 0;
        $distributorCompletionRate = $distributorCampaigns > 0 ? round(($distributorCompleted / $distributorCampaigns) * 100) : 0;

        return [
            'dialer' => [
                'total' => $dialerCampaigns,
                'active' => $dialerActive,
                'inactive' => $dialerInactive,
                'completed' => $dialerCompleted,
                'completionRate' => $dialerCompletionRate
            ],
            'distributor' => [
                'total' => $distributorCampaigns,
                'active' => $distributorActive,
                'inactive' => $distributorInactive,
                'completed' => $distributorCompleted,
                'completionRate' => $distributorCompletionRate
            ]
        ];
    }

    public function render()
    {
        $campaignData = $this->getCampaignTypeData();

        return view('livewire.systems.campaign.campaign-chart', [
            'campaignData' => $campaignData
        ]);
    }
}
