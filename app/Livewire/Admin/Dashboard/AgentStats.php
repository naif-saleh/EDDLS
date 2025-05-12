<?php

namespace App\Livewire\Admin\Dashboard;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AgentStats extends Component
{
    /**
     * Get the current tenant ID
     *
     * @return int
     */
    protected function getTenantId()
    {
        return Auth::user()->tenant_id;
    }

    /**
     * Get entity statistics (count of total, active, inactive)
     *
     * @param string $model The model class name
     * @return array
     */
    protected function getEntityStats($model)
    {
        $tenantId = $this->getTenantId();

        $total = $model::where('tenant_id', $tenantId)->count();
        $active = $model::where('tenant_id', $tenantId)->where('status', 'active')->count();
        $inactive = $model::where('tenant_id', $tenantId)->where('status', 'inactive')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive
        ];
    }

    /**
     * Get campaign statistics
     *
     * @return array
     */
    protected function getDistCampaignStats($campaign_type)
    {
        $tenantId = $this->getTenantId();
        $baseQuery = Campaign::where('tenant_id', $tenantId);

        // For total campaigns
        $total = $baseQuery->count();

        // Filter for dialer campaigns
        $dialerQuery = $baseQuery->where('campaign_type', $campaign_type);

        $active = $dialerQuery->clone()->where('allow', 1)->count();
        $inactive = $dialerQuery->clone()->where('allow', 0)->count();

        $completed = $dialerQuery->clone()
            ->whereDoesntHave('contacts', function($query) {
                $query->whereIn('status', ['new', 'Rounting', 'Talking']);
            })
            ->whereHas('contacts')
            ->count();

        $notStarted = $dialerQuery->clone()
            ->whereHas('contacts', function($query) {
                $query->where('status', 'new');
            })
            ->whereDoesntHave('contacts', function($query) {
                $query->whereIn('status', ['Rounting', 'Talking']);
            })
            ->count();

        $inProgress = $dialerQuery->clone()
            ->whereHas('contacts', function($query) {
                $query->whereIn('status', ['Rounting', 'Talking']);
            })
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'completed' => $completed,
            'notStarted' => $notStarted,
            'inProgress' => $inProgress
        ];
    }



    public function render()
    {
        $agentStats = $this->getEntityStats(Agent::class);
        $providerStats = $this->getEntityStats(Provider::class);
        $campaignDailStats = $this->getDistCampaignStats('dialer');
        $campaignDistlStats = $this->getDistCampaignStats('distributor');


        return view('livewire.admin.dashboard.agent-stats', [
            // Agents
            'totalAgents' => $agentStats['total'],
            'totalActiveAgents' => $agentStats['active'],
            'totalInactiveAgents' => $agentStats['inactive'],

            // Providers
            'totalProvider' => $providerStats['total'],
            'totalActiveProvider' => $providerStats['active'],
            'totalInactiveProvider' => $providerStats['inactive'],

            // Dialer Campaigns
            'totalDialCampaign' => $campaignDailStats['total'],
            'totalDialActiveCampaign' => $campaignDailStats['active'],
            'totalDialInactiveCampaign' => $campaignDailStats['inactive'],
            'totalDialCompeletedCampaign' => $campaignDailStats['completed'],
            'totalDialNotStartedCampaign' => $campaignDailStats['notStarted'],
            'totalDialNotCompletedCampaigns' => $campaignDailStats['inProgress'],

            // Distributor Campaigns
            'totalDistCampaign' => $campaignDistlStats['total'],
            'totalDistActiveCampaign' => $campaignDistlStats['active'],
            'totalDistInactiveCampaign' => $campaignDistlStats['inactive'],
            'totalDistCompeletedCampaign' => $campaignDistlStats['completed'],
            'totalDistNotStartedCampaign' => $campaignDistlStats['notStarted'],
            'totalDistNotCompletedCampaigns' => $campaignDistlStats['inProgress']
        ]);
    }
}
