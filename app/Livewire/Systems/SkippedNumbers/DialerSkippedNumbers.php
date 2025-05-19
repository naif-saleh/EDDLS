<?php

namespace App\Livewire\Systems\SkippedNumbers;

use App\Models\Provider;
use App\Models\SkippedNumber;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;

class DialerSkippedNumbers extends Component
{
    use WithPagination;

    protected $systemLogService;

    public $selectedProvider = '';

    public $searchTerm = '';

    public $perPage = 10;

    public $tenant = '';

    public $showGroupedView = false;

    public $skippedNumbersByProvider = [];

    protected $queryString = [
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
            action: 'view_skipped_numbers',
            description: 'User viewed dialer skipped numbers list',
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
            action: 'search_skipped_numbers',
            description: 'User searched skipped numbers',
            metadata: [
                'search_term' => $this->searchTerm
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
            description: 'User filtered skipped numbers by provider',
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
                action: 'export_skipped_numbers',
                description: 'Started skipped numbers export',
                metadata: [
                    'format' => 'csv',
                    'filters' => [
                        'provider_id' => $this->selectedProvider,
                        'search_term' => $this->searchTerm
                    ]
                ]
            );

            $query = $this->buildBaseQuery()
                ->with(['provider', 'campaign'])
                ->select([
                    'phone_number',
                    'provider_id',
                    'campaign_id',
                    'skip_reason',
                    'file_name',
                    'row_number',
                    'created_at',
                    'raw_data'
                ]);

            $skipped = $query->get();

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="dialer_skipped_numbers_' . now()->format('Y-m-d_H-i-s') . '.csv"',
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
                description: 'Successfully exported skipped numbers',
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
                description: 'Failed to export skipped numbers',
                metadata: [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            
            session()->flash('error', 'Failed to export data. Please try again.');
            return null;
        }
    }

    /**
     * Build the base query used for both rendering and CSV export
     */
    protected function buildBaseQuery()
    {
        return SkippedNumber::where('tenant_id', $this->tenant->id)
            ->whereNull('agent_id')  // Ensure we only get dialer skipped numbers
            ->when($this->selectedProvider, function ($query) {
                return $query->where('provider_id', $this->selectedProvider);
            })
            ->when($this->searchTerm, function ($query) {
                return $query->where(function($q) {
                    $q->where('phone_number', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('skip_reason', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->latest();
    }

    public function render()
    {
        // Get providers with skipped number counts
        $providers = Provider::where('tenant_id', $this->tenant->id)
            ->whereHas('skippedNumbers', function($query) {
                $query->whereNull('agent_id')  // Only count dialer skipped numbers
                      ->where('tenant_id', $this->tenant->id);
            })
            ->select('id', 'name')
            ->withCount(['skippedNumbers' => function($query) {
                $query->whereNull('agent_id')
                      ->where('tenant_id', $this->tenant->id);
            }])
            ->get()
            ->map(function ($provider) {
                return (object) [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'count' => $provider->skipped_numbers_count ?? 0,
                ];
            });

        // Main query with provider filtering
        $query = $this->buildBaseQuery();
        $skippedNumbers = $query->paginate($this->perPage);

        // Get total for all providers
        $totalSkipped = SkippedNumber::where('tenant_id', $this->tenant->id)
            ->whereNull('agent_id')
            ->count();

        return view('livewire.systems.skipped-numbers.dialer-skipped-numbers', [
            'skippedNumbers' => $skippedNumbers,
            'providers' => $providers,
            'totalSkipped' => $totalSkipped,
        ]);
    }
}
