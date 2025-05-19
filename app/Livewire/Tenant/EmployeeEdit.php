<?php

namespace App\Livewire\Tenant;

use App\Models\User;
use App\Services\SystemLogService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class EmployeeEdit extends Component
{
    protected $systemLogService;
    public $employee;
    public $employeeName;
    public $employeeEmail;
    public $employeeRole;

    protected $rules = [
        'employeeName' => 'required|string|min:3|max:255',
        'employeeEmail' => 'required|email',
        'employeeRole' => 'required|in:tenant_admin,agent',
    ];

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    public function mount(User $employee)
    {
        $this->employee = $employee;
        $this->employeeName = $employee->name;
        $this->employeeEmail = $employee->email;
        $this->employeeRole = $employee->role;

        // Log component mount
        $this->systemLogService->log(
            logType: 'page_view',
            action: 'view_employee_edit',
            description: 'User accessed employee edit form',
            metadata: [
                'employee_id' => $employee->id,
                'tenant_id' => auth()->user()->tenant->id
            ]
        );
    }

    public function updateEmployee()
    {
        try {
            $this->validate();

            // Store original values for logging
            $originalData = $this->employee->toArray();

            // Update the employee
            $this->employee->update([
                'name' => $this->employeeName,
                'email' => $this->employeeEmail,
                'role' => $this->employeeRole,
            ]);

            // Log the update
            $this->systemLogService->logUpdate(
                model: $this->employee,
                originalAttributes: $originalData,
                description: "Updated employee: {$this->employee->name}",
                metadata: [
                    'updated_fields' => [
                        'name' => $this->employeeName !== $originalData['name'],
                        'email' => $this->employeeEmail !== $originalData['email'],
                        'role' => $this->employeeRole !== $originalData['role'],
                    ]
                ]
            );

            Toaster::success('Employee updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation failure
            $this->systemLogService->log(
                logType: 'validation',
                action: 'employee_update_validation_failed',
                description: 'Employee update validation failed',
                metadata: [
                    'employee_id' => $this->employee->id,
                    'errors' => $e->errors(),
                    'input' => [
                        'name' => $this->employeeName,
                        'email' => $this->employeeEmail,
                        'role' => $this->employeeRole
                    ]
                ]
            );
            throw $e;
        } catch (\Exception $e) {
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'employee_update_failed',
                description: 'Failed to update employee',
                metadata: [
                    'employee_id' => $this->employee->id,
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            Toaster::error('Failed to update employee: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tenant.employee-edit');
    }
}
