<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Models\Agent;
use App\Models\Provider;
use App\Models\Campaign;
use App\Models\License;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStats extends Component
{
    public $agentCount;
    public $providerCount;
    public $campaignCount;
    public $tenantCount;
    public $licenseStats;
    public $monthlyStats;

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        // Count basic stats
        $this->agentCount = Agent::count();
        $this->providerCount = Provider::count();
        $this->campaignCount = Campaign::count();
        $this->tenantCount = Tenant::count();

        // Get license statistics
        $now = Carbon::now();
        $this->licenseStats = [
            'active' => License::where('is_active', true)
                ->where('valid_from', '<=', $now)
                ->where('valid_until', '>=', $now)
                ->count(),
            'inactive' => License::where('is_active', false)->count(),
            'expired' => License::where('is_active', true)
                ->where('valid_until', '<', $now)
                ->count(),
        ];

        // Get monthly stats for chart
        $this->monthlyStats = $this->getMonthlyStats();
    }

    private function getMonthlyStats()
    {
        $months = collect(range(0, 11))->map(function ($i) {
            $date = Carbon::now()->subMonths($i);
            return [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'date' => $date,
            ];
        })->reverse()->values();

        $stats = [];

        foreach ($months as $month) {
            $startOfMonth = $month['date']->copy()->startOfMonth();
            $endOfMonth = $month['date']->copy()->endOfMonth();

            $stats[] = [
                'month' => $month['month'],
                'agents' => Agent::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                'campaigns' => Campaign::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
                'tenants' => Tenant::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            ];
        }

        return $stats;
    }

    public function render()
    {
        return view('livewire.admin.dashboard.admin-stats', [
            'agentCount' => $this->agentCount,
            'providerCount' => $this->providerCount,
            'campaignCount' => $this->campaignCount,
            'tenantCount' => $this->tenantCount,
            'licenseStats' => $this->licenseStats,
            'monthlyStats' => $this->monthlyStats,
        ]);
    }
}
