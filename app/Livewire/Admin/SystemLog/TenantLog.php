<?php

namespace App\Livewire\Admin\SystemLog;

use App\Models\SystemLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

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

    // Reset pagination when filters change
    protected $queryString = [
        'logType' => ['except' => ''],
        'modelType' => ['except' => ''],
        'userId' => ['except' => ''],
        'action' => ['except' => ''],
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function mount()
    {
        // Cache available filter options
        $this->loadFilterOptions();
    }

    public function loadFilterOptions()
    {
        // Get unique model types
        $this->availableModelTypes = SystemLog::select('model_type')
            ->distinct()
            ->pluck('model_type')
            ->toArray();

        // Get unique log types
        $this->availableLogTypes = SystemLog::select('log_type')
            ->distinct()
            ->pluck('log_type')
            ->toArray();

        // Get unique actions
        $this->availableActions = SystemLog::select('action')
            ->distinct()
            ->pluck('action')
            ->toArray();
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
        $this->resetPage();
    }

    public function viewLogDetails($logId)
    {
        $this->selectedLog = SystemLog::find($logId);
        $this->viewingDetails = true;

        $this->dispatch('open-modal', 'log-details-modal');
    }

    public function closeLogDetails()
    {
        $this->selectedLog = null;
        $this->viewingDetails = false;

        $this->dispatch('close-modal', 'log-details-modal');
    }

    public function getFormattedModelType($modelType)
    {
        // Remove namespace and just return the class name
        $parts = explode('\\', $modelType);
        return end($parts);
    }

    public function exportLogs()
    {
        // You would implement export functionality here
        // This is just a placeholder
        Toaster::info('Export functionality would be implemented here.');
    }

    public function render()
    {
        $query = SystemLog::query();

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
        $logs = $query->with('user')->paginate($this->perPage);

        // Get users for filter dropdown
        $users = User::orderBy('name')->get();



         return view('livewire.admin.system-log.tenant-log', [
            'logs' => $logs,
            'users' => $users
        ]);
    }
}
