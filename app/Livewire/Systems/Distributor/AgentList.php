<?php

namespace App\Livewire\Systems\Distributor;

use App\Jobs\SyncronizeAgentsFromPbxJob;
use App\Models\Agent;
use App\Models\Tenant;
use App\Services\ThreeCXIntegrationService;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;
use App\Services\ThreeCxTokenService;

class AgentList extends Component
{
    use WithPagination;

    public $isActive = false;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $editMode = false;

    public $editingProviderId = null;

    public $confirmingDeleteId = null;

    private $threeCxToken = '';

    public $tokenValue;

    private $three_cxintegration_service = '';

    public $three_cxintegration_service_value;

    public $tenant_id;

    public function mount()
    {
        $this->tenant_id = auth()->user()->tenant_id;

        // Initialize services with the tenant ID
        $threeCxToken = new ThreeCxTokenService($this->tenant_id);
        $three_cxintegration_service = new ThreeCXIntegrationService($threeCxToken, $this->tenant_id);

        // Get token and users data for the tenant
        $this->tokenValue = $threeCxToken->getToken();
        $this->three_cxintegration_service_value = $three_cxintegration_service->getUsersFromThreeCxApi();
    }

    // Syncronize Agents From 3CX PBX
    public function syincAgents()
    {

        // Make sure the parameters are in correct order
        SyncronizeAgentsFromPbxJob::dispatch($this->tenant_id, $this->three_cxintegration_service_value);

        Toaster::success('Agents Synchronization Started');
    }

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

     // Toggle to activate or disactivate agent
     public function toggleAgentStatus($id, $isChecked)
     {
         $agent = Agent::find($id);

         if (! $agent) {
             Toaster::error('agent not found.');

             return;
         }

         $agent->update([
             'status' => $isChecked ? 'active' : 'inactive',
         ]);

         Toaster::success('agent status updated successfully.');
     }

    public function render()
    {
        $query = Agent::query();
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('extension', 'like', '%'.$this->search.'%')
                ->orWhere('status', 'like', '%'.$this->search.'%')
                ->orderBy($this->sortField, $this->sortDirection);
        }
        $query->where('tenant_id', $this->tenant_id)->orderBy($this->sortField, $this->sortDirection);
        $agents = $query->paginate($this->perPage);

        return view('livewire.systems.distributor.agent-list', [
            'agents' => $agents
        ]);
    }
}
