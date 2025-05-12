<?php

namespace App\Exports;

use App\Models\License;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LicenseExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $license;

    public function __construct(License $license)
    {
        $this->license = $license;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view('exports.license-export', [
            'license' => $this->license
        ]);
    }

    /**
     * Style the export
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        // Style the header row
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E5E7EB',
                ],
            ],
        ]);

        // Style for the license info section
        $sheet->getStyle('A2:B5')->applyFromArray([
            'font' => [
                'size' => 12,
            ],
        ]);

        // Style for the table header
        $sheet->getStyle('A7:B7')->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'E5E7EB',
                ],
            ],
        ]);

        // Add borders to all cells in the table
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A7:B' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D1D5DB'],
                ],
            ],
        ]);

        return $sheet;
    }
}
