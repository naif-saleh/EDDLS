<?php

namespace App\Exports\Excel;

use App\Models\CallLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CallLogsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = CallLog::with(['provider', 'contact', 'campaign', 'agent'])
            ->select('call_logs.*');

        if (!empty($this->filters['provider_id'])) {
            $query->where('provider_id', $this->filters['provider_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('call_status', $this->filters['status']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('contact', function ($q) use ($search) {
                    $q->where('phone_number', 'like', "%{$search}%");
                })
                ->orWhereHas('provider', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        return [
            'Call ID',
            'Provider',
            'Campaign',
            'Agent',
            'Phone Number',
            'Call Status',
            'Call Type',
            'Talk Duration',
            'Dial Duration',
            'Start Time',
            'End Time',
            'Notes'
        ];
    }

    public function map($log): array
    {
        return [
            $log->call_id,
            $log->provider->name ?? 'N/A',
            $log->campaign->name ?? 'N/A',
            $log->agent->name ?? 'N/A',
            $log->contact->phone_number ?? 'N/A',
            $log->call_status,
            $log->call_type,
            $log->talking_duration,
            $log->dial_duration,
            $log->created_at->format('Y-m-d H:i:s'),
            $log->updated_at->format('Y-m-d H:i:s'),
            $log->notes
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
} 