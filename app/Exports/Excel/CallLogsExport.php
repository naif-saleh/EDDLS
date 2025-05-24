<?php

namespace App\Exports\Excel;

use App\Models\DialerCallsReport;
use App\Services\TenantService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class CallLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;
    protected $tenant;

    public function __construct($filters = [], $tenant = null)
    {
        $this->filters = $filters;
        $this->tenant = $tenant;
    }
     

    public function collection()
    {
        TenantService::setConnection($this->tenant);
        $query = DialerCallsReport::on('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->select('dialer_calls_reports.*');

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
                  ->orWhere('call_id', 'like', "%{$search}%");
            });
        }

        return $query->latest('date_time')->get();
    }

    public function headings(): array
    {
        return [
            'Call ID',
            'Provider',
            'Campaign',
            'Phone Number',
            'Call Status',
            'Talking Duration',
            'Dialing Duration',
            'Call At',
            'Date Time'
        ];
    }

    public function map($log): array
    {
        return [
            $log->call_id ?? 'N/A',
            $log->provider ?? 'N/A',
            $log->campaign ?? 'N/A',
            $log->phone_number ?? 'N/A',
            $this->getCallStatus($log->call_status),
            $log->talking_duration ?? '00:00:00',
            $log->dialing_duration ?? '00:00:00',
            $log->call_at ? Carbon::parse($log->call_at)->format('Y-m-d H:i:s') : 'N/A',
            $log->date_time ? Carbon::parse($log->date_time)->format('Y-m-d H:i:s') : 'N/A'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F3F4F6']
                ]
            ]
        ];
    }

    protected function getCallStatus($status)
    {
        return $status === 'Talking' ? 'Answered' : ($status === 'Routing' ? 'Unanswered' : $status);
    }
}