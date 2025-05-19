<?php

namespace App\Livewire\Tenant;

use App\Services\SystemLogService;
use Livewire\Component;

class EmployeeList extends Component
{
    protected $systemLogService;

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    public function mount()
    {
        // Log component mount
        $this->systemLogService->log(
            logType: 'page_view',
            action: 'view_employee_list',
            description: 'User viewed employee list',
            metadata: [
                'tenant_id' => auth()->user()->tenant->id
            ]
        );
    }

    public function render()
    {
        $employees = auth()->user()->tenant->users()->get();

        // Log employee list retrieval
        $this->systemLogService->log(
            logType: 'data_access',
            action: 'list_employees',
            description: 'Retrieved employee list',
            metadata: [
                'tenant_id' => auth()->user()->tenant->id,
                'employee_count' => $employees->count()
            ]
        );

        return view('livewire.tenant.employee-list', [
            'employees' => $employees,
        ]);
    }
}
