<?php

namespace App\Livewire\Systems\Distributor;

use App\Models\Agent;
use App\Models\Campaign;
use App\Models\Provider;
use App\Services\DistributorReportService;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class DistributorCallsReport extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $selectedCampaign;
    public $selectedAgent;
    public $selectedProvider;
    public $searchTerm = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $isLoading = false;

    protected $queryString = [
        'startDate',
        'endDate',
        'selectedCampaign',
        'selectedAgent',
        'selectedProvider',
        'searchTerm',
        'sortField',
        'sortDirection',
    ];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        $this->startDate = now()->startOfDay()->format('Y-m-d H:i:s');
        $this->endDate = now()->endOfDay()->format('Y-m-d H:i:s');
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function export()
    {
        $this->isLoading = true;

        try {
            return app(DistributorReportService::class)->exportToExcel([
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'campaign_id' => $this->selectedCampaign,
                'agent_id' => $this->selectedAgent,
                'provider_id' => $this->selectedProvider,
                'search' => $this->searchTerm,
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function getCallsQuery()
    {
        return app(DistributorReportService::class)->getFilteredCallsQuery([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'campaign_id' => $this->selectedCampaign,
            'agent_id' => $this->selectedAgent,
            'provider_id' => $this->selectedProvider,
            'search' => $this->searchTerm,
        ])->orderBy($this->sortField, $this->sortDirection);
    }

    public function getCampaigns()
    {
        return Campaign::where('campaign_type', 'distributor')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
    }

    public function getAgents()
    {
        return Agent::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
    }

    public function getProviders()
    {
        return Provider::where('tenant_id', auth()->user()->tenant_id)
            ->orderBy('name')
            ->get();
    }

    public function resetFilters()
    {
        $this->reset([
            'startDate',
            'endDate',
            'selectedCampaign',
            'selectedAgent',
            'selectedProvider',
            'searchTerm'
        ]);
        $this->startDate = now()->startOfDay()->format('Y-m-d H:i:s');
        $this->endDate = now()->endOfDay()->format('Y-m-d H:i:s');
    }

    public function render()
    {
        $calls = $this->getCallsQuery()->paginate($this->perPage);
        $statistics = app(DistributorReportService::class)->calculateStatistics([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'campaign_id' => $this->selectedCampaign,
            'agent_id' => $this->selectedAgent,
            'provider_id' => $this->selectedProvider,
            'search' => $this->searchTerm,
        ]);

        return view('livewire.systems.distributor.distributor-calls-report', [
            'calls' => $calls,
            'campaigns' => $this->getCampaigns(),
            'agents' => $this->getAgents(),
            'providers' => $this->getProviders(),
            'statistics' => $statistics
        ]);
    }
}
