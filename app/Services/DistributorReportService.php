<?php

namespace App\Services;

use App\Models\CallLog;
use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DistributorCallsExport;

class DistributorReportService
{
    protected $tenant_id;
    protected $systemLogService;

    public function __construct(SystemLogService $systemLogService)
    {
        $this->tenant_id = auth()->user()->tenant_id;
        $this->systemLogService = $systemLogService;
    }

    /**
     * Get filtered calls query
     */
    public function getFilteredCallsQuery(array $filters)
    {
        try {
            // Log query building start
            $this->systemLogService->log(
                logType: 'report',
                action: 'build_distributor_query',
                description: 'Started building distributor calls query',
                metadata: [
                    'filters' => $filters,
                    'tenant_id' => $this->tenant_id
                ]
            );

            $query = CallLog::with(['campaign', 'agent', 'provider', 'contact'])
                ->where('call_type', 'distributor')
                ->whereHas('campaign', function($q) {
                    $q->where('tenant_id', $this->tenant_id);
                });

            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($filters['start_date']),
                    Carbon::parse($filters['end_date']),
                ]);
            }

            if (!empty($filters['campaign_id'])) {
                $query->where('campaign_id', $filters['campaign_id']);
            }

            if (!empty($filters['agent_id'])) {
                $query->where('agent_id', $filters['agent_id']);
            }

            if (!empty($filters['provider_id'])) {
                $query->where('provider_id', $filters['provider_id']);
            }

            if (!empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->whereHas('contact', function ($q) use ($filters) {
                        $q->where('phone_number', 'like', '%' . $filters['search'] . '%');
                    })
                    ->orWhereHas('campaign', function ($q) use ($filters) {
                        $q->where('name', 'like', '%' . $filters['search'] . '%')
                          ->where('tenant_id', $this->tenant_id);
                    })
                    ->orWhereHas('agent', function ($q) use ($filters) {
                        $q->where('name', 'like', '%' . $filters['search'] . '%')
                          ->where('tenant_id', $this->tenant_id);
                    });
                });
            }

            // Log successful query build
            $this->systemLogService->log(
                logType: 'report',
                action: 'distributor_query_built',
                description: 'Successfully built distributor calls query',
                metadata: [
                    'filters' => $filters,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings()
                ]
            );

            return $query;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'distributor_query_failed',
                description: 'Failed to build distributor calls query',
                metadata: [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }

    /**
     * Calculate call statistics
     */
    public function calculateStatistics(array $filters)
    {
        try {
            // Log statistics calculation start
            $this->systemLogService->log(
                logType: 'report',
                action: 'calculate_distributor_statistics',
                description: 'Started calculating distributor call statistics',
                metadata: [
                    'filters' => $filters,
                    'tenant_id' => $this->tenant_id
                ]
            );

            $calls = $this->getFilteredCallsQuery($filters)->get();
            
            $totalCalls = $calls->where('call_type', 'distributor')->count();
            $answered = $calls->where('call_type', 'distributor')->where('call_status', 'Talking')->count();
            $agentUnanswered = $calls->where('call_type', 'distributor')->where('call_status', 'Initiating')->count();
            $unanswered = $calls->where('call_type', 'distributor')->whereNotIn('call_status', ['Talking', 'Initiating'])->count();

            $statistics = [
                'total_calls' => $totalCalls,
                'answered' => $answered,
                'unanswered' => $unanswered,
                'agent_unanswered' => $agentUnanswered
            ];

            // Log successful statistics calculation
            $this->systemLogService->log(
                logType: 'report',
                action: 'distributor_statistics_calculated',
                description: 'Successfully calculated distributor call statistics',
                metadata: [
                    'filters' => $filters,
                    'statistics' => $statistics
                ]
            );

            return $statistics;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'distributor_statistics_failed',
                description: 'Failed to calculate distributor call statistics',
                metadata: [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }

    /**
     * Export calls to Excel
     */
    public function exportToExcel(array $filters)
    {
        try {
            // Log export start
            $this->systemLogService->log(
                logType: 'export',
                action: 'start_distributor_export',
                description: 'Started exporting distributor calls to Excel',
                metadata: [
                    'filters' => $filters,
                    'tenant_id' => $this->tenant_id
                ]
            );

            $calls = $this->getFilteredCallsQuery($filters)->get();
            
            $filename = 'distributor_calls_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            // Log successful export
            $this->systemLogService->log(
                logType: 'export',
                action: 'distributor_export_completed',
                description: 'Successfully exported distributor calls to Excel',
                metadata: [
                    'filters' => $filters,
                    'record_count' => $calls->count(),
                    'filename' => $filename
                ]
            );

            return Excel::download(
                new DistributorCallsExport($calls), 
                $filename
            );
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'distributor_export_failed',
                description: 'Failed to export distributor calls',
                metadata: [
                    'filters' => $filters,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }

    /**
     * Get real-time search results
     */
    public function searchCalls(string $term): Collection
    {
        try {
            // Log search start
            $this->systemLogService->log(
                logType: 'search',
                action: 'search_distributor_calls',
                description: 'Started searching distributor calls',
                metadata: [
                    'search_term' => $term,
                    'tenant_id' => $this->tenant_id
                ]
            );

            $results = $this->getFilteredCallsQuery(['search' => $term])
                ->limit(10)
                ->get();

            // Log successful search
            $this->systemLogService->log(
                logType: 'search',
                action: 'distributor_search_completed',
                description: 'Successfully searched distributor calls',
                metadata: [
                    'search_term' => $term,
                    'result_count' => $results->count()
                ]
            );

            return $results;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'distributor_search_failed',
                description: 'Failed to search distributor calls',
                metadata: [
                    'search_term' => $term,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            throw $e;
        }
    }
}
