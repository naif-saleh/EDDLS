<?php

namespace App\Livewire\Admin\SystemLog;

use App\Models\SystemLog;
use App\Models\User;
use App\Services\TenantService;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

class TenantLog extends Component
{
    use WithPagination;

    // Filters
    public $logType = '';
    public $modelType = '';
    public $userId = '';
    public $action = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $tenantId = ''; // New filter for super admins

    // Search
    public $search = '';

    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // Pagination
    public $perPage = 15;

    // Detail view
    public $selectedLog = null;
    public $viewingDetails = false;

    // Model types cache
    public $availableModelTypes = [];
    public $availableLogTypes = [];
    public $availableActions = [];
    public $availableTenants = []; // New property for super admins

    public $showLogModal = false;
 
    public function viewLogDetails($logId)
    {
        $this->selectedLog = SystemLog::find($logId);
        $this->showLogModal = true;
    }

    public function closeLogDetails()
    {
        $this->showLogModal = false;
        $this->selectedLog = null;
    }
    // Reset pagination when filters change
    protected $queryString = [
        'logType' => ['except' => ''],
        'modelType' => ['except' => ''],
        'userId' => ['except' => ''],
        'action' => ['except' => ''],
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'tenantId' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function mount()
    {
        // Cache available filter options
        $this->loadFilterOptions();

        // If user is not a super admin, set and lock their tenant_id
        if (!Auth::user()->isSuperAdmin()) {
            $this->tenantId = Auth::user()->tenant_id;
        }
    }

    public function loadFilterOptions()
    {
        TenantService::setConnection(Auth::user()->tenant);
        $query = SystemLog::on('tenant');

        // Apply tenant scope for non-super admins
        if (!Auth::user()->isSuperAdmin()) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        } elseif ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        // Get unique model types
        $this->availableModelTypes = $query->select('model_type')
            ->distinct()
            ->pluck('model_type')
            ->toArray();

        // Get unique log types
        $this->availableLogTypes = $query->select('log_type')
            ->distinct()
            ->pluck('log_type')
            ->toArray();

        // Get unique actions
        $this->availableActions = $query->select('action')
            ->distinct()
            ->pluck('action')
            ->toArray();

        // Load available tenants for super admins
        if (Auth::user()->isSuperAdmin()) {
            TenantService::setConnection(Auth::user()->tenant);
            $this->availableTenants = \App\Models\Tenant::on('tenant')->orderBy('name')->get();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingLogType()
    {
        $this->resetPage();
    }

    public function updatingModelType()
    {
        $this->resetPage();
    }

    public function updatingUserId()
    {
        $this->resetPage();
    }

    public function updatingAction()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingTenantId()
    {
        $this->resetPage();
        $this->loadFilterOptions(); // Reload options when tenant changes
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->logType = '';
        $this->modelType = '';
        $this->userId = '';
        $this->action = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search = '';
        // Only reset tenantId if user is super admin
        if (Auth::user()->isSuperAdmin()) {
            $this->tenantId = '';
        }
        $this->resetPage();
    }

    // public function viewLogDetails($logId)
    // {
    //     $query = SystemLog::query();
        
    //     // Ensure tenant scope is respected
    //     if (!Auth::user()->isSuperAdmin()) {
    //         $query->where('tenant_id', Auth::user()->tenant_id);
    //     }

    //     $this->selectedLog = $query->find($logId);
        
    //     if (!$this->selectedLog) {
    //         Toaster::error('Log entry not found or access denied.');
    //         return;
    //     }

    //     $this->viewingDetails = true;
    //     $this->dispatch('open-modal', 'log-details-modal');
    // }

    // public function closeLogDetails()
    // {
    //     $this->selectedLog = null;
    //     $this->viewingDetails = false;
    //     $this->dispatch('close-modal', 'log-details-modal');
    // }

    public function getFormattedModelType($modelType)
    {
        // Remove namespace and just return the class name
        $parts = explode('\\', $modelType);
        return end($parts);
    }

    /**
     * Compute the changes between previous and new data
     *
     * @param array|null $previousData
     * @param array|null $newData
     * @return array
     */
    public function computeChanges($previousData, $newData)
    {
        $changes = [];
        
        if (!$previousData || !$newData) {
            return $changes;
        }

        // Get all unique keys from both arrays
        $allKeys = array_unique(array_merge(array_keys($previousData), array_keys($newData)));

        foreach ($allKeys as $key) {
            // Skip internal fields
            if (in_array($key, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $oldValue = $previousData[$key] ?? null;
            $newValue = $newData[$key] ?? null;

            // Only add to changes if the values are different
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'changed' => true
                ];
            }
        }

        return $changes;
    }

    public function exportLogs()
    {
        // You would implement export functionality here
        // This is just a placeholder
        Toaster::info('Export functionality would be implemented here.');
    }

    public function render()
    {
        TenantService::setConnection(Auth::user()->tenant);
        $query = SystemLog::on('tenant');

        // Apply tenant scope for non-super admins
        if (!Auth::user()->isSuperAdmin()) {
            $query->where('tenant_id', Auth::user()->tenant_id);
        } elseif ($this->tenantId) {
            $query->where('tenant_id', $this->tenantId);
        }

        // Apply filters
        if ($this->logType) {
            $query->where('log_type', $this->logType);
        }

        if ($this->modelType) {
            $query->where('model_type', $this->modelType);
        }

        if ($this->userId) {
            $query->where('user_id', $this->userId);
        }

        if ($this->action) {
            $query->where('action', $this->action);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                  ->orWhere('model_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($userQuery) {
                      $userQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Get logs with pagination and eager load relationships
        $logs = $query->with(['user', 'tenant'])->paginate($this->perPage);

        // Get users for filter dropdown (scoped to tenant for non-super admins)
        $usersQuery = User::orderBy('name');
        if (!Auth::user()->isSuperAdmin()) {
            $usersQuery->where('tenant_id', Auth::user()->tenant_id);
        } elseif ($this->tenantId) {
            $usersQuery->where('tenant_id', $this->tenantId);
        }
        // if(!Auth::user()->isTenantAdmin()){
        //     $usersQuery->where('id', Auth::user()->id)->get();
        // }
        $users = $usersQuery->get();

        return view('livewire.admin.system-log.tenant-log', [
            'logs' => $logs,
            'users' => $users
        ]);
    }
}
