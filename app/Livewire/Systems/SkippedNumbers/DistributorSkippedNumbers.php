<?php

namespace App\Livewire\Systems\SkippedNumbers;

use App\Models\SkippedNumber;
use App\Models\Agent;
use App\Models\Provider;
use App\Services\SystemLogService;
use App\Services\TenantService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class DistributorSkippedNumbers extends Component
{
    use WithPagination;

    protected $systemLogService;

    public $selectedAgent = null;
    public $selectedProvider = null;
    public $searchTerm = '';
    public $perPage = 10;
    public $tenant;
    protected $queryString = [
        'selectedAgent' => ['except' => ''],
        'selectedProvider' => ['except' => ''],
        'searchTerm' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    public function mount($tenant)
    {
        $this->resetPage();
        $this->tenant = $tenant;

        // Log component mount
        $this->systemLogService->log(
            logType: 'page_view',
            action: 'view_distributor_skipped_numbers',
            description: 'User viewed distributor skipped numbers list',
            metadata: [
                'tenant_id' => $this->tenant->id
            ]
        );
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();

        // Log search action
        $this->systemLogService->log(
            logType: 'search',
            action: 'search_distributor_skipped_numbers',
            description: 'User searched distributor skipped numbers',
            metadata: [
                'search_term' => $this->searchTerm
            ]
        );
    }

    public function updatingSelectedAgent()
    {
        $this->resetPage();

        // Log agent filter change
        $this->systemLogService->log(
            logType: 'filter',
            action: 'filter_by_agent',
            description: 'User filtered skipped numbers by agent',
            metadata: [
                'agent_id' => $this->selectedAgent
            ]
        );
    }

    public function updatingSelectedProvider()
    {
        $this->resetPage();

        // Log provider filter change
        $this->systemLogService->log(
            logType: 'filter',
            action: 'filter_by_provider',
            description: 'User filtered distributor skipped numbers by provider',
            metadata: [
                'provider_id' => $this->selectedProvider
            ]
        );
    }

    public function downloadCsv()
    {
        try {
            // Log export start
            $this->systemLogService->log(
                logType: 'export',
                action: 'export_distributor_skipped_numbers',
                description: 'Started distributor skipped numbers export',
                metadata: [
                    'format' => 'csv',
                    'filters' => [
                        'agent_id' => $this->selectedAgent,
                        'provider_id' => $this->selectedProvider,
                        'search_term' => $this->searchTerm
                    ]
                ]
            );

            TenantService::setConnection($this->tenant);
            $query = SkippedNumber::with(['provider', 'agent', 'campaign'])
                ->select([
                    'phone_number',
                    'provider_id',
                    'agent_id',
                    'campaign_id',
                    'skip_reason',
                    'file_name',
                    'row_number',
                    'created_at',
                    'raw_data'
                ])
                ->where('tenant_id', $this->tenant->id)
                ->whereNotNull('agent_id');

            if ($this->selectedAgent) {
                $query->where('agent_id', $this->selectedAgent);
            }

            if ($this->selectedProvider) {
                $query->where('provider_id', $this->selectedProvider);
            }

            if ($this->searchTerm) {
                $query->where(function($q) {
                    $q->where('phone_number', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('skip_reason', 'like', '%' . $this->searchTerm . '%');
                });
            }

            $skipped = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="distributor_skipped_numbers_' . now()->format('Y-m-d_H-i-s') . '.csv"',
                'Pragma' => 'no-cache',
                'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                'Expires' => '0',
            ];

            $callback = function() use ($skipped) {
                $file = fopen('php://output', 'w');
                
                // Write headers
                fputcsv($file, [
                    'Phone Number',
                    'Provider Name',
                    'Provider Extension',
                    'Agent Name',
                    'Agent Extension',
                    'Campaign Name',
                    'Skip Reason',
                    'File Name',
                    'Row Number',
                    'Created At',
                    'Raw Data'
                ]);

                foreach ($skipped as $row) {
                    fputcsv($file, [
                        $row->phone_number,
                        $row->provider->name ?? 'N/A',
                        $row->provider->extension ?? 'N/A',
                        $row->agent->name ?? 'N/A',
                        $row->agent->extension ?? 'N/A',
                        $row->campaign->name ?? 'N/A',
                        $row->skip_reason,
                        $row->file_name,
                        $row->row_number,
                        $row->created_at->format('Y-m-d H:i:s'),
                        is_array($row->raw_data) ? json_encode($row->raw_data) : $row->raw_data
                    ]);
                }

                fclose($file);
            };

            // Log successful export
            $this->systemLogService->log(
                logType: 'export',
                action: 'export_completed',
                description: 'Successfully exported distributor skipped numbers',
                metadata: [
                    'record_count' => $skipped->count(),
                    'format' => 'csv'
                ]
            );

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            // Log the error
            $this->systemLogService->log(
                logType: 'error',
                action: 'export_failed',
                description: 'Failed to export distributor skipped numbers',
                metadata: [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            
            session()->flash('error', 'Failed to export data. Please try again.');
            return null;
        }
    }

    public function render()
    {
        TenantService::setConnection($this->tenant);
        // Get agents with skipped numbers for filter dropdown
        $agents = Agent::select('agents.*', DB::raw('COUNT(skipped_numbers.id) as count'))
            ->join('skipped_numbers', 'agents.id', '=', 'skipped_numbers.agent_id')
            ->whereNotNull('skipped_numbers.agent_id')
            ->groupBy('agents.id')
            ->get();

            TenantService::setConnection($this->tenant);
        // Get providers for filter dropdown
        $providers = Provider::select('providers.*', DB::raw('COUNT(skipped_numbers.id) as count'))
            ->join('skipped_numbers', 'providers.id', '=', 'skipped_numbers.provider_id')
            ->whereNotNull('skipped_numbers.agent_id')  // Only count skipped numbers with agents
            ->groupBy('providers.id')
            ->get();

            TenantService::setConnection($this->tenant);
        // Build the main query
        $query = SkippedNumber::with(['agent', 'provider', 'campaign'])
            ->where('tenant_id', $this->tenant)
            ->whereNotNull('skipped_numbers.agent_id');

        if ($this->selectedAgent) {
            $query->where('agent_id', $this->selectedAgent);
        }

        if ($this->selectedProvider) {
            $query->where('provider_id', $this->selectedProvider);
        }

        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('phone_number', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('skip_reason', 'like', '%' . $this->searchTerm . '%');
            });
        }

        // Get totals for stats
        $totalSkipped = $query->count();

        // Get paginated results
        $skippedNumbers = $query->paginate($this->perPage);

        return view('livewire.systems.skipped-numbers.distributor-skipped-numbers', [
           'skippedNumbers' => $skippedNumbers,
            'agents' => $agents,
            'providers' => $providers,
            'totalSkipped' => $totalSkipped
        ]);
    }
}
