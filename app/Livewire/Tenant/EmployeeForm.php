<?php

namespace App\Livewire\Tenant;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class EmployeeForm extends Component
{
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

    public function createEmployee()
    {
        $this->validate();

        try {
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

            // Handle additional role setup if needed
            if ($this->employeeRole === 'agent') {
                // Create the agent record if needed
                // You might need to implement this based on your application's requirements
            }

            Toaster::success('Employee Created Successfully');
            $this->reset(['EmplyeeName', 'employeeEmail', 'employeePassword', 'employeePhone', 'employeeRole']);
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create employee: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tenant.employee-form');
    }
}
