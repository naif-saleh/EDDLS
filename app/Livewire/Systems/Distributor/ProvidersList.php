<?php

namespace App\Livewire\Systems\Distributor;

use App\Models\Agent;
use App\Models\Provider;
use App\Services\TenantService;
use Dotenv\Exception\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Masmerise\Toaster\Toaster;

class ProvidersList extends Component
{
    use WithPagination;

    public $providerName = '';

    public $providerExtension = '';

    public $providerStatus = '';

    public $isActive = false;

    public $search = '';

    public $sortField = 'created_at';

    public $sortDirection = 'desc';

    public $perPage = 10;

    public $editMode = false;

    public $editingProviderId = null;

    public $confirmingDeleteId = null;

    // public $agent;

    // public function mount(Agent $agent){
    //     TenantService::setConnection(auth()->user()->tenant);
    //     $this->agent = $agent;
    // }
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

    // Create New Provider
    public function createProvider()
    {
        TenantService::setConnection(auth()->user()->tenant);
       $originalConnection = DB::getDefaultConnection();

        try {
            TenantService::setConnection(auth()->user()->tenant);
            DB::setDefaultConnection('tenant');

            $validator = Validator::make([
                'providerName' => $this->providerName,
                'providerExtension' => $this->providerExtension,
                'providerStatus' => $this->providerStatus,
            ], [
                'providerName' => [
                    'required', 'string', 'max:150',
                    Rule::unique('providers', 'name')
                        ->where(fn ($q) => $q->where('tenant_id', auth()->user()->tenant->id)),
                ],
                'providerExtension' => [
                    'required',
                    Rule::unique('providers', 'extension')
                        ->where(fn ($q) => $q->where('tenant_id', auth()->user()->tenant->id)),
                ],
                'providerStatus' => 'required',
            ]);

            $validated = $validator->validate();

        } catch (ValidationException $e) {
            DB::setDefaultConnection($originalConnection);
            Toaster::error('Validation failed: ' . implode(', ', $e->validator->errors()->all()));
            return redirect()->route('tenant.distributor.providers', ['tenant' => auth()->user()->tenant->slug]);
        }

        DB::setDefaultConnection($originalConnection);

        TenantService::setConnection(auth()->user()->tenant);
        // Generate a base slug
        $baseSlug = Str::slug($this->providerName);

        // Check if the slug exists and generate a unique one if needed
        $slug = $baseSlug;
        $count = 1;

        while (Provider::on('tenant')->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        Provider::on('tenant')->create([
            'name' => $this->providerName,
            'extension' => $this->providerExtension,
            'tenant_id' => auth()->user()->tenant->id,
            'status' => $this->providerStatus,
            'slug' => $slug,
            'provider_type' => 'dialer', // Default value for provider_type
        ]);

        Toaster::success('Provider is Created Successfully');

       return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
    }

    // Edit Modal display
    public function openEditModal($providerId)
    {
        TenantService::setConnection(auth()->user()->tenant);
        $provider = Provider::on('tenant')->find($providerId);

        if ($provider) {
            $this->editingProviderId = $provider->id;
            $this->providerName = $provider->name;
            $this->providerExtension = $provider->extension;
            $this->providerStatus = $provider->status;
            $this->editMode = true;

            $this->dispatch('open-modal'); // open the modal via Livewire event
        }
    }

    // Update Provider
    public function updateProvider()
    {
        TenantService::setConnection(auth()->user()->tenant);
        $this->validate([
            'providerName' => 'required|string|max:150',
            'providerExtension' => 'required|string|max:10',
            'providerStatus' => 'required',
        ]);

        $provider = Provider::on('tenant')->find($this->editingProviderId);

        if ($provider) {
            $provider->update([
                'name' => $this->providerName,
                'extension' => $this->providerExtension,
                'status' => $this->providerStatus,
            ]);

            $this->reset(['editMode', 'editingProviderId', 'providerName', 'providerExtension', 'providerStatus']);
            $this->dispatch('close-modal');

            Toaster::success('Provider updated successfully!');

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('Provider not found.');

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }
    }

    // Toggle to activate or disactivate Provider
    public function toggleProviderStatus($id, $isChecked)
    {
        TenantService::setConnection(auth()->user()->tenant);
        $provider = Provider::on('tenant')->find($id);

        if (! $provider) {
            Toaster::error('Provider not found.');

            return;
        }

        $provider->update([
            'status' => $isChecked ? 'active' : 'inactive',
        ]);

        Toaster::success('provider status updated successfully.');
    }

    public function confirmDelete($providerId)
    {
        $this->confirmingDeleteId = $providerId;
    }

    public function deleteTenant()
    {
        TenantService::setConnection(auth()->user()->tenant);
        $provider = Provider::on('tenant')->find($this->confirmingDeleteId);

        if ($provider) {
            $provider->delete();
            Toaster::success('Provider deleted successfully.');

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('Provider not found.');

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }

        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        TenantService::setConnection(auth()->user()->tenant);
        // Initialize provider query
        $query = Provider::on('tenant')->where('tenant_id', auth()->user()->tenant->id);

        // Apply search if provided
        if ($this->search) {
            $query->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('extension', 'like', '%'.$this->search.'%')
                ->orWhere('status', 'like', '%'.$this->search.'%');
        }

        // Filter providers by the current user's tenant
        $query
            ->orderBy($this->sortField, $this->sortDirection);

        // Get providers with pagination
        $providers = $query->paginate($this->perPage);

        // Get the current agent from the URL if available
        $agentSlug = request()->route('agent');
        $agent = null;

        if ($agentSlug) {
            $agent = Agent::where('slug', $agentSlug)
                ->where('tenant_id', auth()->user()->tenant_id)
                ->first();
        }

        // Fallback to any agent if needed
        if (! $agent) {
            $agent = Agent::where('tenant_id', auth()->user()->tenant_id)->first();
        }


        return view('livewire.systems.distributor.providers-list', [
            'providers' => $providers,
            'agent' => $agent,
        ]);
    }
}
