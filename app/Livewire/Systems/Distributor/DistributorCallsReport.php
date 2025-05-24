<?php

namespace App\Livewire\Systems\Distributor;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use App\Services\DistributorReportService;
use App\Services\SystemLogService;
use App\Exports\DistributorCallsExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class DistributorCallsReport extends Component
{
    use WithPagination;

    public $search = '';
    public $agent = '';
    public $provider = '';
    public $campaign = '';
    public $status = '';
    public $date_from = '';
    public $date_to = '';

    // For sorting
    public $sortField = 'date_time';
    public $sortDirection = 'desc';

    // For template variables
    public $searchTerm = '';
    public $selectedAgent = '';
    public $selectedProvider = '';
    public $selectedCampaign = '';
    public $startDate = '';
    public $endDate = '';

    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'agent' => ['except' => ''],
        'provider' => ['except' => ''],
        'campaign' => ['except' => ''],
        'status' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => '']
    ];

    public function mount()
    {
        // Sync template variables with component properties
        $this->searchTerm = $this->search;
        $this->selectedAgent = $this->agent;
        $this->selectedProvider = $this->provider;
        $this->selectedCampaign = $this->campaign;
        $this->startDate = $this->date_from;
        $this->endDate = $this->date_to;
    }

    // Handle search input
    public function updatedSearchTerm()
    {
        $this->search = $this->searchTerm;
        $this->resetPage();

        $this->getSystemLogService()->log(
            logType: 'search',
            action: 'dialer_calls_search',
            description: 'User searched dialer calls',
            metadata: ['search_term' => $this->search]
        );
    }

    // Handle agent selection
    public function updatedSelectedAgent()
    {
        $this->agent = $this->selectedAgent;
        $this->resetPage();
    }

    // Handle provider selection
    public function updatedSelectedProvider()
    {
        $this->provider = $this->selectedProvider;
        $this->resetPage();
    }

    // Handle campaign selection
    public function updatedSelectedCampaign()
    {
        $this->campaign = $this->selectedCampaign;
        $this->resetPage();
    }

    // Handle start date
    public function updatedStartDate()
    {
        $this->date_from = $this->startDate;
        $this->resetPage();
    }

    // Handle end date
    public function updatedEndDate()
    {
        $this->date_to = $this->endDate;
        $this->resetPage();
    }

    // Legacy updaters for backward compatibility
    public function updatingSearch()
    {
        $this->resetPage();
        $this->getSystemLogService()->log(
            logType: 'search',
            action: 'dialer_calls_search',
            description: 'User searched dialer calls',
            metadata: ['search_term' => $this->search]
        );
    }

    public function updatingAgent()
    {
        $this->resetPage();
    }

    public function updatingProvider()
    {
        $this->resetPage();
    }

    public function updatingCampaign()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
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
        $this->resetPage();
    }

    public function getCallStatuses()
    {
        return [
            'Talking' => 'Answered',
            'Routing' => 'Unanswered',
            'Initiating' => 'Agent Unanswered'
        ];
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'agent',
            'provider',
            'campaign',
            'status',
            'date_from',
            'date_to',
            'searchTerm',
            'selectedAgent',
            'selectedProvider',
            'selectedCampaign',
            'startDate',
            'endDate'
        ]);

        $this->getSystemLogService()->log(
            logType: 'ui_action',
            action: 'reset_call_filters',
            description: 'User reset call report filters'
        );
    }

    public function export()
    {
        $filters = [
            'search' => $this->search,
            'agent' => $this->agent,
            'provider' => $this->provider,
            'campaign' => $this->campaign,
            'status' => $this->status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        $this->getSystemLogService()->log(
            logType: 'export',
            action: 'export_call_logs',
            description: 'User exported call logs',
            metadata: [
                'filters' => $filters,
                'format' => 'xlsx'
            ]
        );

        $filename = 'distributor-call-logs-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $tenant = Auth::user()->tenant;
        return Excel::download(new DistributorCallsExport($filters, $tenant), $filename);
    }

    public function render(DistributorReportService $reportService)
    {
        $filters = [
            'search' => $this->search,
            'agent' => $this->agent,
            'provider' => $this->provider,
            'campaign' => $this->campaign,
            'status' => $this->status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        $statistics = $reportService->getCallStatistics($filters);
        $callLogs = $reportService->getFilteredCallLogs($filters);

        // Log report view (only on initial load, not on Livewire updates)
        if (!request()->wantsJson()) {
            $this->getSystemLogService()->log(
                logType: 'page_view',
                action: 'view_call_report',
                description: 'User viewed dialer calls report',
                metadata: [
                    'filters' => $filters,
                    'statistics' => [
                        'total_calls' => $statistics['total_calls'] ?? 0,
                        'answered_calls' => $statistics['answered'] ?? 0,
                        'unanswered_calls' => $statistics['unanswered'] ?? 0,
                        'agent_unanswered_calls' => $statistics['agent_unanswered'] ?? 0
                    ]
                ]
            );
        }

        return view('livewire.systems.distributor.distributor-calls-report', [
            'calls' => $callLogs,
            'agents' => $reportService->getAgents(),
            'providers' => $reportService->getProviders(),
            'campaigns' => $reportService->getCampaigns(),
            'statuses' => $this->getCallStatuses(),
            'reportService' => $reportService,
            'statistics' => $statistics
        ]);
    }
}
