<?php

namespace App\Livewire\Systems\Campaign;

use App\Models\Campaign;
use App\Models\Provider;
use App\Models\Tenant;
use Livewire\Component;
use Livewire\WithPagination;

class DistributorCampaignsDetails extends Component
{
    use WithPagination;
    
    public $tenant;
    public $provider;
    public $campaign;
    public $filterStatus = 'all'; // Default filter value

    public function mount(Tenant $tenant, Provider $provider, Campaign $campaign)
    {
        $this->tenant = $tenant;
        $this->provider = $provider;
        $this->campaign = $campaign->load('contacts');
    }

    public function setFilter($status)
    {
        $this->filterStatus = $status;
    }

    public function render()
    {
        $countContacts = $this->campaign->contacts->where('campaign_id', $this->campaign->id)->count();

        // Apply filters to contacts query
        $contactsQuery = $this->campaign->contacts()->where('campaign_id', $this->campaign->id);

        // Apply status filter
        if ($this->filterStatus === 'all') {
            $contactsQuery = $contactsQuery; // No additional filtering, return all
        } else {
            $contactsQuery = $contactsQuery->where(function ($query) {
            if ($this->filterStatus === 'new') {
                $query->where('status', 'new');
            } elseif ($this->filterStatus === 'unanswered') {
                $query->where('status', 'Routing');
            } elseif ($this->filterStatus === 'answered') {
                $query->where('status', 'Talking');
            }
            });
        }

        // Count called contacts (status not 'new')
        $calledContacts = $this->campaign->contacts()->where('campaign_id', $this->campaign->id)->where('status', '!=', 'new')->count();

        return view('livewire.systems.campaign.distributor-campaigns-details', [
            'countContacts' => $countContacts,
            'calledContacts' => $calledContacts,
            'contacts' => $contactsQuery->paginate(50)
        ]);
    }
}
