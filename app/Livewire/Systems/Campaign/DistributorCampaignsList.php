<?php

namespace App\Livewire\Systems\Campaign;

use App\Exports\Excel\CampaignContactsExport;
use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use App\Models\Tenant;
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
        } else {
            $this->selectedCampaigns = [];
        }
    }

    // make Sorting
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Activate Campaign
    public function toggleCampaignActivation($id)
    {
        $campaign = $this->provider->campaigns()->where('id', $id)->first();

        $campaign->update([
            'allow' => ! $campaign->allow,
        ]);
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
    }

    public function deleteSelectedCampaigns()
    {
        if (count($this->selectedCampaigns) > 0) {
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
            Campaign::find($this->campaignIdToDelete)->delete();
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
