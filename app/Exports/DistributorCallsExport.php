<?php

namespace App\Exports;

use App\Models\DistributorCallsReport;
use App\Services\DistributorReportService;
use App\Services\TenantService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DistributorCallsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;
    protected $reportService;
    protected $tenant;

    public function __construct($filters = [], $tenant)
    {
        $this->tenant = $tenant;
        $this->filters = $filters;
        $this->reportService = app(DistributorReportService::class);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // $tenant_id = auth()->user()->tenant_id;

        TenantService::setConnection($this->tenant);
        $query = DistributorCallsReport::where('tenant_id', $this->tenant->id);

        // Apply filters
        if (!empty($this->filters['agent'])) {
            $query->where('agent', 'like', "%{$this->filters['agent']}%");
        }

        if (!empty($this->filters['provider'])) {
            $query->where('provider', 'like', "%{$this->filters['provider']}%");
        }

        if (!empty($this->filters['campaign'])) {
            $query->where('campaign', 'like', "%{$this->filters['campaign']}%");
        }

        if (!empty($this->filters['status'])) {
            $query->where('call_status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date_time', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('date_time', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone_number', 'like', "%{$search}%")
                  ->orWhere('provider', 'like', "%{$search}%")
                  ->orWhere('campaign', 'like', "%{$search}%")
                  ->orWhere('agent', 'like', "%{$search}%");
            });
        }

        return $query->latest('date_time')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Date/Time',
            'Campaign',
            'Agent',
            'Provider',
            'Phone Number',
            'Call Status',
            'Dialing Duration',
            'Talking Duration',
            'Call Time',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            Carbon::parse($row->date_time)->format('Y-m-d H:i:s'),
            $row->campaign ?? 'N/A',
            $row->agent ?? 'N/A',
            $row->provider ?? 'N/A',
            $row->phone_number ?? 'N/A',
            $this->reportService->getCallStatus($row->call_status),
            $row->dialing_duration ?? '00:00:00',
            $row->talking_duration ?? '00:00:00',
            $row->call_at ? Carbon::parse($row->call_at)->format('H:i:s') : 'N/A'
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }
}
