<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LicenseExport;

class LicenseContent extends Component
{
    public $license;

    public function mount($license_id)
    {
        $this->license = License::with('tenant')->findOrFail($license_id);
    }

    public function exportLicensePDF()
    {
         $pdf = PDF::loadView('exports.license-pdf', [
            'license' => $this->license
        ]);

         $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
            'dpi' => 150,
            'isPhpEnabled' => true,
        ]);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'license_certificate_' . $this->license->id . '.pdf');


    }

    public function render()
    {
        return view('livewire.admin.licenses.license-content');
    }
}
