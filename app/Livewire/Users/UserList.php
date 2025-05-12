<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class UserList extends Component
{
    use WithPagination;

    public $userName = '';

    public $userEmail = '';

    public $userPassword = '';

    public $userStatus = '';

    public $userTenant = '';

    public $isActive = false;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $editMode = false;

    public $editingUserId = null;

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
    public function createUser()
    {
        try {
            $validated = $this->validate([
                'userName' => 'required|string|max:150',
                'userEmail' => 'required|email|max:150|unique:users,email',
                'userPassword' => 'required|string|min:4|max:15',
             ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Toaster::error('Validation failed: '.implode(', ', $e->validator->errors()->all()));

            return redirect()->route('admin.user.list');
        }

        User::create([
            'name' => $this->userName,
            'email' => $this->userEmail,
            'password' => Hash::make($this->userPassword),
         ]);

        Toaster::success('User is Created Successfully');

        return redirect()->route('admin.user.list');
    }

    // Edit Modal display
    public function openEditModal($userId)
    {
        $user = User::find($userId);

        if ($user) {
            $this->editingUserId = $user->id;
            $this->userName = $user->name;
            $this->userEmail = $user->email;
            $this->userPassword = $user->password;
            $this->userStatus = $user->status;
            $this->editMode = true;

            $this->dispatch('open-modal'); // open the modal via Livewire event
        }
    }

    // Update Tenant
    public function updateUser()
    {
        try {
            $this->validate([
                'userName' => 'required|string|max:150',
                'userEmail' => 'required|email|max:150,'.$this->editingUserId,
                'userPassword' => 'required|string|min:6|max:150|',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Toaster::error('Validation failed: '.implode(', ', $e->validator->errors()->all()));

            return redirect()->route('admin.user.list');
        }


        $user = User::find($this->editingUserId);

        if ($user) {
            $user->update([
                'name' => $this->userName,
                'email' => $this->userEmail,
                'password' => $this->userPassword,
            ]);

            $this->reset(['editMode', 'editingUserId', 'userName', 'userEmail', 'userPassword']);
            $this->dispatch('close-modal');

            Toaster::success('User updated successfully!');

            return redirect()->route('admin.user.list');
        } else {
            Toaster::error('User not found.');

            return redirect()->route('admin.user.list');
        }
    }


    public function changeUserRole($userId, $role)
    {
        $user = User::find($userId);
        $user->update(['role' => $role]);
        Toaster::success('User Role Changed to '.$role);
    }


    public function confirmDelete($userId)
    {
        $this->confirmingDeleteId = $userId;
    }

    public function deleteUser()
    {
        $user = User::find($this->confirmingDeleteId);

        if ($user) {
            $user->delete();
            Toaster::success('User deleted successfully.');
        } else {
            Toaster::error('User not found.');
        }

        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        $query = User::query();
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('status', 'like', '%'.$this->search.'%')
                ->orderBy($this->sortField, $this->sortDirection);
        }
        $query->orderBy($this->sortField, $this->sortDirection);
        $users = $query->paginate($this->perPage);
        return view('livewire.users.user-list', [
            'users' => $users
        ]);
    }
}
