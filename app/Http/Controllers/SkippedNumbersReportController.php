<?php

namespace App\Http\Controllers;

use App\Models\SkippedNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class SkippedNumbersReportController extends Controller
{
    /**
     * Display the skipped numbers report view
     */
    // public function index()
    // {
    //     return view('admin.reports.skipped-numbers');
    // }

    /**
     * Get skipped numbers grouped by provider for report
     */
    public function getGroupedByProvider()
    {
        $providers = \App\Models\Provider::select(
                'providers.id',
                'providers.name',
                'providers.extension',
                'providers.status',
                'providers.provider_type',
                DB::raw('COUNT(skipped_numbers.id) as total_skipped'),
                DB::raw('MAX(skipped_numbers.created_at) as last_skipped'),
                DB::raw('COUNT(DISTINCT skipped_numbers.file_name) as files_affected')
            )
            ->join('skipped_numbers', 'providers.id', '=', 'skipped_numbers.provider_id')
            ->whereNull('skipped_numbers.agent_id')
            ->groupBy('providers.id', 'providers.name', 'providers.extension', 'providers.status', 'providers.provider_type')
            ->get();

        return response()->json($providers);
    }

    /**
     * Get details for a specific provider
     */
    public function getProviderDetails($providerId)
    {
        $details = SkippedNumber::where('provider_id', $providerId)
            ->whereNull('agent_id')
            ->get();

        return response()->json($details);
    }

    /**
     * Download skipped numbers report by provider
     */
    public function downloadProviderReport($providerId = null)
    {
        $query = SkippedNumber::with('provider')
            ->select('skipped_numbers.*')
            ->whereNull('agent_id');

        if ($providerId) {
            $query->where('provider_id', $providerId);
        }

        $skipped = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="skipped_numbers_report.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($skipped) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Phone Number', 'Provider Name', 'Provider Extension', 'Provider Type', 'Batch ID', 'File Name', 'Skip Reason', 'Row Number', 'Created At']);

            foreach ($skipped as $row) {
                $providerName = $row->provider ? $row->provider->name : 'Unknown';
                $providerExt = $row->provider ? $row->provider->extension : 'Unknown';
                $providerType = $row->provider ? $row->provider->provider_type : 'Unknown';

                fputcsv($file, [
                    $row->phone_number,
                    $providerName,
                    $providerExt,
                    $providerType,
                    $row->batch_id,
                    $row->file_name,
                    $row->skip_reason,
                    $row->row_number,
                    $row->created_at
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Get summary statistics
     */
    public function getSummary()
    {
        $summary = [
            'total_skipped' => SkippedNumber::count(),
            'unassigned' => SkippedNumber::whereNull('agent_id')->count(),
            'providers_affected' => SkippedNumber::distinct('provider_id')->count('provider_id'),
            'by_reason' => SkippedNumber::select('skip_reason', DB::raw('COUNT(*) as count'))
                ->groupBy('skip_reason')
                ->get(),
            'recent' => SkippedNumber::orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json($summary);
    }
}
