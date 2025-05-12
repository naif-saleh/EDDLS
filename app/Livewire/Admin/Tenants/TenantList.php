<?php

namespace App\Livewire\Admin\Tenants;

use App\Models\Tenant;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class TenantList extends Component
{
    use WithPagination;

    public $tenantName = '';

    public $tenantEmail = '';

    public $tenantPhone = '';

    public $tenantStatus = '';

    public $isActive = false;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $editMode = false;

    public $editingTenantId = null;

    public $confirmingDeleteId = null;

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

    // Create New Tenant
    public function createTenant()
    {
        try {
            $validated = $this->validate([
                'tenantName' => 'required|string|max:150',
                'tenantEmail' => 'required|email|max:150|unique:tenants,email',
                'tenantPhone' => 'required|numeric|digits_between:8,12',
                'tenantStatus' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Toaster::error('Validation failed: '.implode(', ', $e->validator->errors()->all()));

            return redirect()->route('admin.tenant.list');
        }

        Tenant::create([
            'name' => $this->tenantName,
            'email' => $this->tenantEmail,
            'phone' => $this->tenantPhone,
            'status' => $this->tenantStatus,
        ]);

        Toaster::success('Tenant is Created Successfully');

        return redirect()->route('admin.tenant.list');
    }

    // Edit Modal display
    public function openEditModal($tenantId)
    {
        $tenant = Tenant::find($tenantId);

        if ($tenant) {
            $this->editingTenantId = $tenant->id;
            $this->tenantName = $tenant->name;
            $this->tenantEmail = $tenant->email;
            $this->tenantPhone = $tenant->phone;
            $this->tenantStatus = $tenant->status;
            $this->editMode = true;

            $this->dispatch('open-modal'); // open the modal via Livewire event
        }
    }

    // Update Tenant
    public function updateTenant()
    {
        $this->validate([
            'tenantName' => 'required|string|max:150',
            'tenantEmail' => 'required|email|max:150|unique:tenants,email,'.$this->editingTenantId,
            'tenantPhone' => 'required|integer',
            'tenantStatus' => 'required',
        ]);

        $tenant = Tenant::find($this->editingTenantId);

        if ($tenant) {
            $tenant->update([
                'name' => $this->tenantName,
                'email' => $this->tenantEmail,
                'phone' => $this->tenantPhone,
                'status' => $this->tenantStatus,
            ]);

            $this->reset(['editMode', 'editingTenantId', 'tenantName', 'tenantEmail', 'tenantPhone', 'tenantStatus']);
            $this->dispatch('close-modal');

            Toaster::success('Tenant updated successfully!');

            return redirect()->route('admin.tenant.list');
        } else {
            Toaster::error('Tenant not found.');

            return redirect()->route('admin.tenant.list');
        }
    }

    // Toggle to activate or disactivate Tenant
    public function toggleTenantStatus($id, $isChecked)
    {
        $tenant = Tenant::find($id);

        if (! $tenant) {
            Toaster::error('Tenant not found.');

            return;
        }

        $tenant->update([
            'status' => $isChecked ? 'active' : 'inactive',
        ]);

        Toaster::success('Tenant status updated successfully.');
    }

    public function confirmDelete($tenantId)
    {
        $this->confirmingDeleteId = $tenantId;
    }

    public function deleteTenant()
    {
        $tenant = Tenant::find($this->confirmingDeleteId);

        if ($tenant) {
            $tenant->delete();
            Toaster::success('Tenant deleted successfully.');
        } else {
            Toaster::error('Tenant not found.');
        }

        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        $query = Tenant::query();
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('status', 'like', '%'.$this->search.'%')
                ->orderBy($this->sortField, $this->sortDirection);
        }
        $query->orderBy($this->sortField, $this->sortDirection);
        $tenants = $query->paginate($this->perPage);

        return view('livewire.admin.tenants.tenant-list', [
            'tenants' => $tenants,
        ]);
    }
}
