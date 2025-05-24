<?php

namespace App\Livewire\Systems\Distributor;

use App\Jobs\SyncronizeAgentsFromPbxJob;
use App\Models\Agent;
use App\Models\License;
use App\Services\LicenseService;
use App\Services\TenantService;
use App\Services\ThreeCXIntegrationService;
use App\Services\ThreeCxTokenService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class AgentList extends Component
{
    use WithPagination;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $editMode = false;
    public $editingProviderId = null;
    public $confirmingDeleteId = null;

    public $tenant_id;
    public $tenant;
    public $license;

    public $tokenValue;
    public $three_cxintegration_service_value;

    public function mount($tenant = null)
    {
        if (!$tenant) {
            $tenant = request()->route('tenant');
        }

        $this->tenant = $tenant;
        $this->tenant_id = auth()->user()->tenant_id;

        if ($tenant) {
            $this->license = DB::connection('mysql')
                ->table('licenses')
                ->where('tenant_id', $tenant->id)
                ->first();
        }

        $tokenService = new ThreeCxTokenService($this->tenant_id);
        $integrationService = new ThreeCXIntegrationService($this->tenant_id, $tokenService);

        $this->tokenValue = $tokenService->getToken();
        $this->three_cxintegration_service_value = $integrationService->getUsersFromThreeCxApi();
    }

    protected function getLicenseService(): LicenseService
    {
        return new LicenseService;
    }

    public function updatingSearch()
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

    public function syincAgents()
    {
        TenantService::setConnection(auth()->user()->tenant);

        SyncronizeAgentsFromPbxJob::dispatch($this->tenant_id, $this->three_cxintegration_service_value);

        Toaster::success('Agents synchronization started.');
    }

    public function toggleAgentStatus($id, $isChecked)
    {
        TenantService::setConnection(auth()->user()->tenant);

        $agent = Agent::on('tenant')->find($id);

        if (! $agent) {
            Toaster::error('Agent not found.');
            return;
        }

        $licenseService = $this->getLicenseService();

        if ($isChecked) {
            if (! $licenseService->validAgentsCount($this->tenant_id)) {
                Toaster::warning('License agent limit reached. Please contact support.');
                $this->dispatch('revertCheckbox', agentId: $id, currentStatus: $agent->status === 'active');
                return redirect()->route('tenant.distributor.agents', ['tenant' => $this->tenant_id]);
            }

            $agent->update(['status' => 'active']);
            return redirect()->route('tenant.distributor.agents', ['tenant' => $this->tenant_id]);
        } else {
            $licenseService->incrementAgentsCount($this->tenant_id);
            $agent->update(['status' => 'inactive']);
            return redirect()->route('tenant.distributor.agents', ['tenant' => $this->tenant_id]);
        }

        Toaster::success('Agent status updated successfully.');
    }

    public function render()
    {
        TenantService::setConnection(auth()->user()->tenant);

        $query = Agent::on('tenant')->where('tenant_id', $this->tenant_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('extension', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%');
            });
        }

        $agents = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.systems.distributor.agent-list', [
            'agents' => $agents,
            // 'license' => $this->license, // Uncomment if used in the view
        ]);
    }
}
