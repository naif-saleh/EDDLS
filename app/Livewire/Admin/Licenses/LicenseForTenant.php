<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LicenseForTenant extends Component
{

    public $tenant;

    // Mount method to receive the tenant
    public function mount(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function render()
    {
        TenantService::setConnection($this->tenant);
         $license = DB::connection('mysql')
        ->table('licenses')
        ->where('tenant_id', $this->tenant->id)
        ->first();

        // dd( $license);

    return view('livewire.admin.licenses.license-for-tenant', [
        'license' => $license,
    ]);
    }
}
