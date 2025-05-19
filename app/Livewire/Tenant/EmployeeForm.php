<?php

namespace App\Livewire\Tenant;

use App\Models\User;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class EmployeeForm extends Component
{
    protected $systemLogService;

    public $EmplyeeName;
    public $employeeEmail;
    public $employeePassword;
    public $employeePhone;
    public $employeeRole;

    protected $rules = [
        'EmplyeeName' => 'required|string|min:3|max:255',
        'employeeEmail' => 'required|email|unique:users,email',
        'employeePassword' => 'required|min:8',
        'employeeRole' => 'required|in:tenant_admin,agent',
    ];

    protected $messages = [
        'EmplyeeName.required' => 'The employee name is required.',
        'employeeEmail.required' => 'The employee email is required.',
        'employeeEmail.email' => 'Please enter a valid email address.',
        'employeeEmail.unique' => 'This email is already taken.',
        'employeePassword.required' => 'The password is required.',
        'employeePassword.min' => 'The password must be at least 8 characters.',
        'employeeRole.required' => 'Please select a role for this employee.',
        'employeeRole.in' => 'The selected role is invalid.',
    ];

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    public function mount()
    {
        // Log component mount
        $this->systemLogService->log(
            logType: 'page_view',
            action: 'view_employee_form',
            description: 'User accessed employee creation form',
            metadata: [
                'tenant_id' => auth()->user()->tenant->id
            ]
        );
    }

    public function createEmployee()
    {
        try {
            $this->validate();

            // Log validation success
            $this->systemLogService->log(
                logType: 'validation',
                action: 'employee_validation_passed',
                description: 'Employee creation form validation passed',
                metadata: [
                    'employee_name' => $this->EmplyeeName,
                    'employee_email' => $this->employeeEmail,
                    'employee_role' => $this->employeeRole
                ]
            );

            // Get the current tenant
            $tenant = auth()->user()->tenant;

            // Create the user
            $user = User::create([
                'name' => $this->EmplyeeName,
                'email' => $this->employeeEmail,
                'password' => Hash::make($this->employeePassword),
                'tenant_id' => $tenant->id,
                'role' => $this->employeeRole,
            ]);

            // Log user creation
            $this->systemLogService->logCreate(
                model: $user,
                description: "Created new employee: {$user->name}",
                metadata: [
                    'role' => $this->employeeRole,
                    'tenant_id' => $tenant->id
                ]
            );

            // Handle additional role setup if needed
            if ($this->employeeRole === 'agent') {
                // Log agent role setup
                $this->systemLogService->log(
                    logType: 'role_setup',
                    action: 'agent_role_setup',
                    model: $user,
                    description: 'Setting up agent role for new employee',
                    metadata: [
                        'user_id' => $user->id,
                        'role' => 'agent'
                    ]
                );
                // Create the agent record if needed
                // You might need to implement this based on your application's requirements
            }

            Toaster::success('Employee Created Successfully');
            $this->reset(['EmplyeeName', 'employeeEmail', 'employeePassword', 'employeePhone', 'employeeRole']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation failure
            $this->systemLogService->log(
                logType: 'validation',
                action: 'employee_validation_failed',
                description: 'Employee creation form validation failed',
                metadata: [
                    'errors' => $e->errors(),
                    'input' => [
                        'name' => $this->EmplyeeName,
                        'email' => $this->employeeEmail,
                        'role' => $this->employeeRole
                    ]
                ]
            );
            throw $e;
        } catch (\Exception $e) {
            // Log general error
            $this->systemLogService->log(
                logType: 'error',
                action: 'employee_creation_failed',
                description: 'Failed to create employee',
                metadata: [
                    'error' => $e->getMessage(),
                    'stack_trace' => $e->getTraceAsString()
                ]
            );
            session()->flash('error', 'Failed to create employee: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tenant.employee-form');
    }
}
