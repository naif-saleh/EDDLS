<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LicenseExport;
use Barryvdh\DomPDF\Facade\Pdf;
class LicenseList extends Component
{
    use WithPagination;


    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;




    // Update page while search
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // make Sorting
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function exportLicensePDF($license_id)
    {
        $license  = License::find($license_id);
         $pdf = PDF::loadView('exports.license-pdf', [
            'license' => $license
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
        }, 'license_certificate_' . $license->id . '.pdf');


    }



    public function render()
    {
         // Initialize License query
         $query = License::query();

         // Apply search if provided
         if ($this->search) {
            $query->whereHas('tenant', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            });
         }


         $query->orderBy($this->sortField, $this->sortDirection);

         // Get license with pagination
         $licenses = $query->paginate($this->perPage);






        return view('livewire.admin.licenses.license-list', ['licenses' => $licenses]);
    }
}
