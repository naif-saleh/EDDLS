<?php

namespace App\Livewire\Tenant;

use App\Models\User;
use App\Services\SystemLogService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class EmployeeList extends Component
{
    protected $systemLogService;
    public $confirmingDeleteId = null;

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

     // Toggle to activate or disactivate Employee
    public function toggleEmployeeStatus($id, $isChecked)
    {
        $employee = User::find($id);

        if (! $employee) {
            Toaster::error('Employee not found.');
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'Employee_not_found',
                description: "Attempted to toggle status of non-existent Employee ID: {$id}"
            );

            return;
        }

        $originalStatus = $employee->status;
        $newStatus = $isChecked ? 1 : 0;

        $employee->update([
            'status' => $newStatus,
        ]);

        // Log status change
        $this->systemLogService->log(
            logType: 'status_change',
            action: 'Employee_status_changed',
            model: $employee,
            description: 'Employee status changed',
            previousData: ['status' => $originalStatus],
            newData: ['status' => $newStatus]
        );

        Toaster::success('Employee status updated successfully.');
    }


    public function confirmDelete($employeeId)
    {
        $this->confirmingDeleteId = $employeeId;
        // Log delete confirmation
        $employee = User::find($employeeId);
        if ($employee) {
            $this->systemLogService->log(
                logType: 'ui_action',
                action: 'confirm_delete',
                model: $employee,
                description: "User initiated delete confirmation for employee: {$employee->name}"
            );
        }
    }

    public function deleteTenant()
    {
        $employee = User::find($this->confirmingDeleteId);
        if (! $employee) {
            Toaster::error('Employee not found.');
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'employee_not_found',
                description: "Attempted to delete non-existent employee ID: {$this->confirmingDeleteId}"
            );

            return redirect()->route('tenant.employees.list', ['tenant' => auth()->user()->tenant->slug]);
        }
        // Check if the employee is the last admin
        if ($employee->role === 'tenant_admin' && $employee->tenant->users()->where('role', 'tenant_admin')->count() <= 1) {
            Toaster::error('Cannot delete the last admin employee.');
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'last_admin_employee_deletion_attempt',
                description: "Attempted to delete the last admin employee ID: {$this->confirmingDeleteId}"
            );

            return redirect()->route('tenant.employees.list', ['tenant' => auth()->user()->tenant->slug]);
        }
        if ($employee) {
            // Log before deletion
            $this->systemLogService->logDelete(
                model: $employee,
                description: "Deleted employee: {$employee->name}"
            );
            $employee->delete();
            Toaster::success('employee deleted successfully.');


            return redirect()->route('tenant.employees.list', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('employee not found.');

            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'employee_not_found',
                description: "Attempted to delete non-existent employee ID: {$this->confirmingDeleteId}"
            );

            return redirect()->route('tenant.employees.list', ['tenant' => auth()->user()->tenant->slug]);
        }

        $this->confirmingDeleteId = null;
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
