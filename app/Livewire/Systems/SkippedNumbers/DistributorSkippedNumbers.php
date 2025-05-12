<?php

namespace App\Livewire\Systems\SkippedNumbers;

use App\Models\SkippedNumber;
use App\Models\Agent;
use App\Models\Provider;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class DistributorSkippedNumbers extends Component
{
    use WithPagination;

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

    public function mount($tenant)
    {
        $this->resetPage();
        $this->tenant = $tenant;
    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingSelectedAgent()
    {
        $this->resetPage();
    }

    public function updatingSelectedProvider()
    {
        $this->resetPage();
    }

    public function downloadCsv()
    {
        $query = SkippedNumber::query()
            ->select('phone_number', 'provider_id', 'agent_id', 'campaign_id', 'skip_reason', 'created_at', 'raw_data')
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
            'Content-Disposition' => 'attachment; filename="agent_skipped_numbers.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($skipped) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Phone Number', 'Provider ID', 'Agent ID', 'Campaign ID', 'Skip Reason', 'Created At', 'Raw Data']);

            foreach ($skipped as $row) {
                fputcsv($file, [
                    $row->phone_number,
                    $row->provider_id,
                    $row->agent_id,
                    $row->campaign_id,
                    $row->skip_reason,
                    $row->created_at,
                    $row->raw_data
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function render()
    {
        // Get agents with skipped numbers for filter dropdown
        $agents = Agent::select('agents.*', DB::raw('COUNT(skipped_numbers.id) as count'))
            ->join('skipped_numbers', 'agents.id', '=', 'skipped_numbers.agent_id')
            ->whereNotNull('skipped_numbers.agent_id')
            ->groupBy('agents.id')
            ->get();

        // Get providers for filter dropdown
        $providers = Provider::select('providers.*', DB::raw('COUNT(skipped_numbers.id) as count'))
            ->join('skipped_numbers', 'providers.id', '=', 'skipped_numbers.provider_id')
            ->whereNotNull('skipped_numbers.agent_id')  // Only count skipped numbers with agents
            ->groupBy('providers.id')
            ->get();

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
