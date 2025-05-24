<?php

namespace App\Services;

use App\Models\DialerCallsReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DialerReportService
{
    protected $tenant_id;
    protected $systemLogService;

    public function __construct(SystemLogService $systemLogService)
    {
        $this->tenant_id = auth()->user()->tenant_id;
        $this->systemLogService = $systemLogService;
    }

    public function getFilteredCallLogs($filters = [])
    {
        try {
            // Log report generation start
            $this->systemLogService->log(
                logType: 'report',
                action: 'generate_dialer_report',
                description: 'Started generating dialer call logs report',
                metadata: [
                    'filters' => $filters,
                    'tenant_id' => $this->tenant_id
                ]
            );

            TenantService::setConnection(Auth::user()->tenant);
            $query = DialerCallsReport::on('tenant')
                ->where('tenant_id', $this->tenant_id)
                ->select('dialer_calls_reports.*');

            if (!empty($filters['provider'])) {
                $query->where('provider', 'like', "%{$filters['provider']}%");
            }

            if (!empty($filters['campaign'])) {
                $query->where('campaign', 'like', "%{$filters['campaign']}%");
            }

            if (!empty($filters['status'])) {
                $query->where('call_status', $filters['status']);
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('date_time', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('date_time', '<=', $filters['date_to']);
            }

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%")
                      ->orWhere('provider', 'like', "%{$search}%")
                      ->orWhere('campaign', 'like', "%{$search}%")
                      ->orWhere('call_id', 'like', "%{$search}%");
                });
            }

            $results = $query->latest('date_time')->paginate(10);

            // Log successful report generation
            $this->systemLogService->log(
                logType: 'report',
                action: 'dialer_report_generated',
                description: 'Successfully generated dialer call logs report',
                metadata: [
                    'filters' => $filters,
                    'record_count' => $results->total(),
                    'page' => $results->currentPage()
                ]
            );

            return $results;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'dialer_report_failed',
                description: 'Failed to generate dialer call logs report',
                metadata: [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }

    public function getCallStatistics($filters = [])
    {
        try {
            // Log statistics calculation start
            $this->systemLogService->log(
                logType: 'report',
                action: 'calculate_dialer_statistics',
                description: 'Started calculating dialer call statistics',
                metadata: [
                    'filters' => $filters,
                    'tenant_id' => $this->tenant_id
                ]
            );

            TenantService::setConnection(Auth::user()->tenant);
            $query = DialerCallsReport::on('tenant')
                ->where('tenant_id', $this->tenant_id);

            if (!empty($filters['provider'])) {
                $query->where('provider', 'like', "%{$filters['provider']}%");
            }

            if (!empty($filters['campaign'])) {
                $query->where('campaign', 'like', "%{$filters['campaign']}%");
            }

            if (!empty($filters['date_from'])) {
                $query->whereDate('date_time', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('date_time', '<=', $filters['date_to']);
            }

            $totalCalls = $query->count();
            $answeredCalls = (clone $query)->where('call_status', 'Talking')->count();
            $unansweredCalls = (clone $query)->where('call_status', 'Routing')->count();

            $statistics = [
                'total' => [
                    'count' => $totalCalls,
                    'label' => 'Total Calls',
                    'icon' => 'M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z',
                    'color' => 'blue'
                ],
                'answered' => [
                    'count' => $answeredCalls,
                    'label' => 'Answered Calls',
                    'icon' => 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z',
                    'color' => 'green'
                ],
                'unanswered' => [
                    'count' => $unansweredCalls,
                    'label' => 'Unanswered Calls',
                    'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'color' => 'red'
                ]
            ];

            // Log successful statistics calculation
            $this->systemLogService->log(
                logType: 'report',
                action: 'dialer_statistics_calculated',
                description: 'Successfully calculated dialer call statistics',
                metadata: [
                    'filters' => $filters,
                    'statistics' => [
                        'total_calls' => $totalCalls,
                        'answered_calls' => $answeredCalls,
                        'unanswered_calls' => $unansweredCalls
                    ]
                ]
            );

            return $statistics;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'dialer_statistics_failed',
                description: 'Failed to calculate dialer call statistics',
                metadata: [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }

    public function getCallStatus($status)
    {
        return $status === 'Talking' ? 'Answered' : ($status === 'Routing' ? 'Unanswered' : $status);
    }

    public function getProviders()
    {
        TenantService::setConnection(Auth::user()->tenant);
        return DialerCallsReport::on('tenant')->where('tenant_id', $this->tenant_id)
            ->distinct()
            ->pluck('provider')
            ->filter()
            ->sort()
            ->values();
    }

    public function getCampaigns()
    {
        TenantService::setConnection(Auth::user()->tenant);
        return DialerCallsReport::on('tenant')->where('tenant_id', $this->tenant_id)
            ->distinct()
            ->pluck('campaign')
            ->filter()
            ->sort()
            ->values();
    }
}