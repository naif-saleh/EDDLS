<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use Livewire\Component;

class LicenseForTenant extends Component
{
    public function render()
    {
        $license = License::where('tenant_id', auth()->user()->tenant_id)->first();
        return view('livewire.admin.licenses.license-for-tenant', [
            'license' => $license,
        ]);
    }
}
