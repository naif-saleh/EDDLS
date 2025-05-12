<?php

namespace App\Livewire\Systems\SkippedNumbers;

use App\Models\Provider;
use App\Models\SkippedNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;

class DialerSkippedNumbers extends Component
{
    use WithPagination;

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

    public function mount($tenant)
    {
        $this->resetPage();
        $this->tenant = $tenant;

    }

    public function updatingSearchTerm()
    {
        $this->resetPage();
    }

    public function updatingSelectedProvider()
    {
        $this->resetPage();
    }

    public function downloadCsv()
    {
        $query = $this->buildBaseQuery();

        $skipped = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="skipped_numbers.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($skipped) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Phone Number', 'Provider', 'Extension', 'Campaign', 'Skip Reason', 'Created At']);

            foreach ($skipped as $row) {
                fputcsv($file, [
                    $row->phone_number,
                    $row->provider->name ?? 'N/A',
                    $row->provider->extension ?? 'N/A',
                    $row->campaign->name ?? 'N/A',
                    $row->skip_reason,
                    $row->created_at,
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Build the base query used for both rendering and CSV export
     */
    protected function buildBaseQuery()
{
    return SkippedNumber::where('tenant_id', $this->tenant->id)
        ->when($this->selectedProvider, function ($query) {
            // Filter by selected provider if one is chosen
            return $query->where('provider_id', $this->selectedProvider);
        })
        ->when($this->searchTerm, function ($query) {
            return $query->where('phone_number', 'like', '%' . $this->searchTerm . '%')
                ->orWhere('skip_reason', 'like', '%' . $this->searchTerm . '%');
        })
        ->latest();
}






public function render()
{
    // Get providers with skipped number counts
    $providers = Provider::where('tenant_id', $this->tenant->id)
        ->withCount(['skippedNumbers' => function ($query) {
            $query->where('tenant_id', $this->tenant->id);
        }])
        ->select('id', 'name')
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
    $totalSkipped = SkippedNumber::where('tenant_id', $this->tenant->id)->count();

    return view('livewire.systems.skipped-numbers.dialer-skipped-numbers', [
        'skippedNumbers' => $skippedNumbers,
        'providers' => $providers,
        'totalSkipped' => $totalSkipped,
    ]);
}
}
