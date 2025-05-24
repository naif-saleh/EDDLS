<?php

namespace App\Livewire\Systems\Dialer;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\DialerReportService;
use App\Services\SystemLogService;
use App\Exports\Excel\CallLogsExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;

class DialerCallsReport extends Component
{
    use WithPagination;

    public $search = '';
    public $provider = '';
    public $campaign = '';
    public $status = '';
    public $date_from = '';
    public $date_to = '';
    
    protected function getSystemLogService(): SystemLogService
    {
        return app(SystemLogService::class);
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'provider' => ['except' => ''],
        'campaign' => ['except' => ''],
        'status' => ['except' => ''],
        'date_from' => ['except' => ''],
        'date_to' => ['except' => '']
    ];

    public function updatingSearch()
    {
        $this->resetPage();

        // Log search action
        $this->getSystemLogService()->log(
            logType: 'search',
            action: 'dialer_calls_search',
            description: 'User searched dialer calls',
            metadata: [
                'search_term' => $this->search
            ]
        );
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

    public function getCallStatuses()
    {
        return [
            'Talking' => 'Answered',
            'Routing' => 'Unanswered'
        ];
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'provider',
            'campaign',
            'status',
            'date_from',
            'date_to'
        ]);

        // Log filter reset
        $this->getSystemLogService()->log(
            logType: 'ui_action',
            action: 'reset_call_filters',
            description: 'User reset call report filters'
        );
    }

    public function exportCallLogs()
    {
        $filters = [
            'search' => $this->search,
            'provider' => $this->provider,
            'campaign' => $this->campaign,
            'status' => $this->status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        // Log export action
        $this->getSystemLogService()->log(
            logType: 'export',
            action: 'export_call_logs',
            description: 'User exported call logs',
            metadata: [
                'filters' => $filters,
                'format' => 'xlsx'
            ]
        );

        $filename = 'call-logs-' . Str::random(8) . '.xlsx';
        $tenant = Auth::user()->tenant;
        return Excel::download(new CallLogsExport($filters, $tenant), $filename);
    }

    public function render(DialerReportService $reportService)
    {
        $filters = [
            'search' => $this->search,
            'provider' => $this->provider,
            'campaign' => $this->campaign,
            'status' => $this->status,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];

        $statistics = $reportService->getCallStatistics($filters);

        // Log report view (only on initial load, not on Livewire updates)
        if (!request()->wantsJson()) {
            $this->getSystemLogService()->log(
                logType: 'page_view',
                action: 'view_call_report',
                description: 'User viewed dialer calls report',
                metadata: [
                    'filters' => $filters,
                    'statistics' => [
                        'total_calls' => $statistics['total']['count'] ?? 0,
                        'answered_calls' => $statistics['answered']['count'] ?? 0,
                        'unanswered_calls' => $statistics['unanswered']['count'] ?? 0
                    ]
                ]
            );
        }
        
        return view('livewire.systems.dialer.dialer-calls-report', [
            'callLogs' => $reportService->getFilteredCallLogs($filters),
            'providers' => $reportService->getProviders(),
            'campaigns' => $reportService->getCampaigns(),
            'statuses' => $this->getCallStatuses(),
            'reportService' => $reportService,
            'statistics' => $statistics
        ]);
    }
}