<?php

namespace App\Livewire\Tenant;

use Livewire\Component;

class EmployeeList extends Component
{

    public function render()
    {
        return view('livewire.tenant.employee-list', [
            'employees' => auth()->user()->tenant->users()->get(),
        ]);
    }
}
