<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DistributorCallsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $calls;

    public function __construct(Collection $calls)
    {
        $this->calls = $calls;
    }

    public function collection()
    {
        return $this->calls;
    }

    public function headings(): array
    {
        return [
            'Date/Time',
            'Campaign',
            'Agent',
            'Provider',
            'Phone Number',
            'Status',
            'Dial Duration',
            'Talking Duration',
            'Total Duration',
        ];
    }

    public function map($call): array
    {
        // Calculate total duration
        $totalDuration = $this->calculateTotalDuration($call->dial_duration, $call->talking_duration);

        return [
            $call->created_at->format('Y-m-d H:i:s'),
            $call->campaign->name ?? 'N/A',
            $call->agent->name ?? 'N/A',
            $call->provider->name ?? 'N/A',
            $call->contact->phone_number ?? 'N/A',
            $call->call_status === 'Initiating' ? 'AgentUnanswered' : $call->call_status,
            $call->dial_duration ?? '00:00:00',
            $call->talking_duration ?? '00:00:00',
            $totalDuration,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    protected function calculateTotalDuration(?string $dialDuration, ?string $talkingDuration): string
    {
        $totalSeconds = 0;

        // Add dial duration
        if ($dialDuration) {
            $parts = explode(':', $dialDuration);
            if (count($parts) === 3) {
                $totalSeconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
        }

        // Add talking duration
        if ($talkingDuration) {
            $parts = explode(':', $talkingDuration);
            if (count($parts) === 3) {
                $totalSeconds += ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
            }
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    }
} 