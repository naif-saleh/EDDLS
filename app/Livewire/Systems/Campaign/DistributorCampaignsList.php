<?php

namespace App\Livewire\Systems\Campaign;

use App\Exports\Excel\CampaignContactsExport;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use App\Models\Tenant;
use App\Services\SystemLogService;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;
use Masmerise\Toaster\Toaster;

class DistributorCampaignsList extends Component
{
    use WithPagination;

    protected $excel;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $provider = '';

    public $agent = '';

    public $tenant = '';

    public $selectedCampaigns = [];

    public $selectAll = false;

    public $confirmingDelete = false;

    public $campaignIdToDelete = '';

    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    public function mount(Tenant $tenant, Provider $provider, Agent $agent, Excel $excel)
    {
        abort_unless($provider->tenant_id === $tenant->id, 403, 'Provider does not belong to tenant.');
        $this->excel = $excel;
        $this->provider = $provider;
        $this->tenant = $tenant;
        $this->agent = $agent;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedCampaigns = $this->provider->campaigns()->pluck('id')->map(fn ($id) => (string) $id)->toArray();
            
            // Log select all action
            $this->getSystemLogService()->log(
                logType: 'ui_action',
                action: 'select_all_distributor_campaigns',
                description: 'Selected all distributor campaigns',
                metadata: [
                    'provider_id' => $this->provider->id,
                    'agent_id' => $this->agent->id,
                    'campaign_count' => count($this->selectedCampaigns)
                ]
            );
        } else {
            $this->selectedCampaigns = [];
            
            // Log deselect all action
            $this->getSystemLogService()->log(
                logType: 'ui_action',
                action: 'deselect_all_distributor_campaigns',
                description: 'Deselected all distributor campaigns',
                metadata: [
                    'provider_id' => $this->provider->id,
                    'agent_id' => $this->agent->id
                ]
            );
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Log sort action
        $this->getSystemLogService()->log(
            logType: 'ui_action',
            action: 'sort_distributor_campaigns',
            description: "Sorted distributor campaigns by {$field}",
            metadata: [
                'sort_field' => $field,
                'sort_direction' => $this->sortDirection,
                'agent_id' => $this->agent->id
            ]
        );
    }

    public function toggleCampaignActivation($id)
    {
        $campaign = $this->provider->campaigns()->where('id', $id)->first();
        $previousStatus = $campaign->allow;

        $campaign->update([
            'allow' => !$campaign->allow,
        ]);

        // Log campaign activation/deactivation
        $this->getSystemLogService()->log(
            logType: 'status_change',
            action: 'distributor_campaign_activation_toggle',
            model: $campaign,
            description: $campaign->allow ? 'Distributor campaign activated' : 'Distributor campaign deactivated',
            previousData: ['allow' => $previousStatus],
            newData: ['allow' => $campaign->allow],
            metadata: [
                'provider_id' => $this->provider->id,
                'agent_id' => $this->agent->id
            ]
        );

        if ($campaign->allow == 1) {
            Toaster::success('Campaign Activated Successfully');
        } else {
            Toaster::success('Campaign Disactivated Successfully');
        }
    }

    public function confirmDelete($id = null)
    {
        $this->campaignIdToDelete = $id;
        $this->confirmingDelete = true;

        // Log delete confirmation
        if ($id) {
            $campaign = Campaign::find($id);
            if ($campaign) {
                $this->getSystemLogService()->log(
                    logType: 'ui_action',
                    action: 'confirm_delete_distributor_campaign',
                    model: $campaign,
                    description: "Initiated delete confirmation for distributor campaign",
                    metadata: [
                        'provider_id' => $this->provider->id,
                        'agent_id' => $this->agent->id
                    ]
                );
            }
        }
    }

    public function deleteSelectedCampaigns()
    {
        if (count($this->selectedCampaigns) > 0) {
            // Log before deletion
            $campaigns = Campaign::whereIn('id', $this->selectedCampaigns)->get();
            foreach ($campaigns as $campaign) {
                $this->getSystemLogService()->logDelete(
                    model: $campaign,
                    description: "Deleted distributor campaign in bulk operation",
                    metadata: [
                        'provider_id' => $this->provider->id,
                        'agent_id' => $this->agent->id,
                        'bulk_delete' => true
                    ]
                );
            }

            Campaign::whereIn('id', $this->selectedCampaigns)->delete();
            $this->selectedCampaigns = [];
            $this->selectAll = false;
            $this->confirmingDelete = false;
            Toaster::success('Selected Campaigns Deleted Successfully.');
        }
    }

    public function deleteCampaign()
    {
        if ($this->campaignIdToDelete) {
            $campaign = Campaign::find($this->campaignIdToDelete);
            if ($campaign) {
                // Log single campaign deletion
                $this->getSystemLogService()->logDelete(
                    model: $campaign,
                    description: "Deleted single distributor campaign",
                    metadata: [
                        'provider_id' => $this->provider->id,
                        'agent_id' => $this->agent->id
                    ]
                );

                $campaign->delete();
            }
            $this->confirmingDelete = false;
            $this->campaignIdToDelete = null;
            Toaster::success('Campaign Deleted Successfully.');
        } else {
            $this->deleteSelectedCampaigns();
        }
    }

    public function downloadContacts($campaign)
    {
        $campaign = Campaign::findOrFail($campaign);

        // Log export action
        $this->getSystemLogService()->log(
            logType: 'export',
            action: 'export_distributor_campaign_contacts',
            model: $campaign,
            description: "Exported distributor campaign contacts",
            metadata: [
                'provider_id' => $this->provider->id,
                'agent_id' => $this->agent->id,
                'export_format' => 'xlsx'
            ]
        );

        Toaster::success('Campaign Exporting Now...');
        $filename = 'campaign_contacts.xlsx';

        return Excel::download(
            new CampaignContactsExport($campaign->id),
            $filename
        );
    }

    public function render()
    {
        $query = $this->provider->campaigns()->newQuery()
            ->join('providers', 'campaigns.provider_id', '=', 'providers.id')
            ->select('campaigns.*');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('campaigns.name', 'like', '%'.$this->search.'%')
                    ->orWhere('providers.name', 'like', '%'.$this->search.'%')
                    ->orWhere('campaigns.status', 'like', '%'.$this->search.'%');
            });
        }

        $query->where('campaigns.tenant_id', auth()->user()->tenant_id)
            ->where('agent_id', $this->agent->id)
            ->where('campaign_type', 'distributor')
            ->orderBy('campaigns.'.$this->sortField, $this->sortDirection)->get();

        $campaigns = $query->paginate($this->perPage);

        return view('livewire.systems.campaign.distributor-campaigns-list', [
            'campaigns' => $campaigns,
        ]);
    }
}
