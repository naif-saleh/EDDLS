<?php

namespace App\Livewire\Systems\Dialer;

use App\Models\Agent;
use App\Models\License;
use App\Models\Provider;
use App\Services\LicenseService;
use App\Services\SystemLogService;
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

    public $licenseSevice;

    protected $systemLogService;

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    protected function getLicenseService()
    {
        return new LicenseService;
    }

    // Update page while search
    public function updatingSearch()
    {
        $this->resetPage();
        // Log search action
        $this->systemLogService->log(
            logType: 'search',
            action: 'provider_search',
            description: 'User searched for providers',
            metadata: ['search_term' => $this->search]
        );
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

        // Log sort action
        $this->systemLogService->log(
            logType: 'list_action',
            action: 'sort_providers',
            description: 'User sorted providers list',
            metadata: [
                'sort_field' => $field,
                'sort_direction' => $this->sortDirection,
            ]
        );
    }

    // Create New Provider
    public function createProvider()
    {
        $licenseService = $this->getLicenseService();
        if (! $licenseService->validProvidersCount(auth()->user()->tenant->id)) {
            Toaster::warning('License Providers limit reached. Please contact support.');
            // Log license limit reached
            $this->systemLogService->log(
                logType: 'license',
                action: 'license_limit_reached',
                description: 'User attempted to create a provider but hit license limit',
                metadata: [
                    'license_type' => 'provider',
                    'tenant_id' => auth()->user()->tenant->id,
                ]
            );

            return;
        }
        try {
            $validated = $this->validate([
                'providerName' => [
                    'required', 'string', 'max:150',
                    Rule::unique('providers', 'name')->where(function ($query) {
                        return $query->where('tenant_id', auth()->user()->tenant->id);
                    }),
                ],
                'providerExtension' => [
                    'required',
                    Rule::unique('providers', 'extension')->where(function ($query) {
                        return $query->where('tenant_id', auth()->user()->tenant->id);
                    }),
                ],
                'providerStatus' => 'required',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Toaster::error('Validation failed: '.implode(', ', $e->validator->errors()->all()));

            // Log validation error
            $this->systemLogService->log(
                logType: 'validation',
                action: 'provider_validation_failed',
                description: 'Provider creation validation failed',
                metadata: [
                    'errors' => $e->validator->errors()->all(),
                    'input' => [
                        'name' => $this->providerName,
                        'extension' => $this->providerExtension,
                        'status' => $this->providerStatus,
                    ],
                ]
            );

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }

        // Generate a base slug
        $baseSlug = Str::slug($this->providerName);

        // Check if the slug exists and generate a unique one if needed
        $slug = $baseSlug;
        $count = 1;

        while (Provider::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$count;
            $count++;
        }

        Provider::create([
            'name' => $this->providerName,
            'extension' => $this->providerExtension,
            'tenant_id' => auth()->user()->tenant->id,
            'status' => $this->providerStatus,
            'slug' => $slug,
            'provider_type' => 'dialer', // Default value for provider_type
        ]);

        // Log provider creation
        $this->systemLogService->logCreate(
            model: $provider,
            description: "Created new provider: {$provider->name}"
        );

        Toaster::success('Provider is Created Successfully');

        return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
    }

    // Edit Modal display
    public function openEditModal($providerId)
    {
        $provider = Provider::find($providerId);

        if ($provider) {
            $this->editingProviderId = $provider->id;
            $this->providerName = $provider->name;
            $this->providerExtension = $provider->extension;
            $this->providerStatus = $provider->status;
            $this->editMode = true;

            $this->dispatch('open-modal'); // open the modal via Livewire event

            // Log edit modal opened
            $this->systemLogService->log(
                logType: 'ui_action',
                action: 'open_edit_modal',
                model: $provider,
                description: "Opened edit modal for provider: {$provider->name}"
            );
        }
    }

    // Update Provider
    public function updateProvider()
    {
        $this->validate([
            'providerName' => 'required|string|max:150',
            'providerExtension' => 'required|string|max:10',
            'providerStatus' => 'required',
        ]);

        $provider = Provider::find($this->editingProviderId);

        if ($provider) {
            $provider->update([
                'name' => $this->providerName,
                'extension' => $this->providerExtension,
                'status' => $this->providerStatus,
            ]);

            // Log provider update
            $this->systemLogService->logUpdate(
                model: $provider,
                originalAttributes: $originalData,
                description: "Updated provider: {$provider->name}"
            );

            $this->reset(['editMode', 'editingProviderId', 'providerName', 'providerExtension', 'providerStatus']);
            $this->dispatch('close-modal');

            Toaster::success('Provider updated successfully!');

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('Provider not found.');

            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'provider_not_found',
                description: "Attempted to update non-existent provider ID: {$this->editingProviderId}"
            );

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }
    }

    // Toggle to activate or disactivate Provider
    public function toggleProviderStatus($id, $isChecked)
    {
        $provider = Provider::find($id);

        if (! $provider) {
            Toaster::error('Provider not found.');
            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'provider_not_found',
                description: "Attempted to toggle status of non-existent provider ID: {$id}"
            );

            return;
        }

        $provider->update([
            'status' => $isChecked ? 'active' : 'inactive',
        ]);

        // Log status change
        $this->systemLogService->log(
            logType: 'status_change',
            action: 'provider_status_changed',
            model: $provider,
            description: 'Provider status changed',
            previousData: ['status' => $originalStatus],
            newData: ['status' => $newStatus]
        );

        Toaster::success('provider status updated successfully.');
    }

    public function confirmDelete($providerId)
    {
        $this->confirmingDeleteId = $providerId;
        // Log delete confirmation
        $provider = Provider::find($providerId);
        if ($provider) {
            $this->systemLogService->log(
                logType: 'ui_action',
                action: 'confirm_delete',
                model: $provider,
                description: "User initiated delete confirmation for provider: {$provider->name}"
            );
        }
    }

    public function deleteTenant()
    {
        $provider = Provider::find($this->confirmingDeleteId);

        if ($provider) {
            // Log before deletion
            $this->systemLogService->logDelete(
                model: $provider,
                description: "Deleted provider: {$provider->name}"
            );
            $provider->delete();
            Toaster::success('Provider deleted successfully.');
            $licenseService = $this->getLicenseService();
            // Decrement the provider count in the license service
            $licenseService->icrementProvidersCount(auth()->user()->tenant_id);

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('Provider not found.');

            // Log error
            $this->systemLogService->log(
                logType: 'error',
                action: 'provider_not_found',
                description: "Attempted to delete non-existent provider ID: {$this->confirmingDeleteId}"
            );

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }

        $this->confirmingDeleteId = null;
    }

    public function render()
    {
        // Initialize provider query
        $query = Provider::query()->where('tenant_id', auth()->user()->tenant->id);

        // Apply search if provided - with proper grouping
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('extension', 'like', '%'.$this->search.'%')
                    ->orWhere('status', 'like', '%'.$this->search.'%');
            });
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

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

        $license = License::where('tenant_id', auth()->user()->tenant_id)->first();

        // Log page view (only on initial load, not on Livewire updates)
        if (! request()->wantsJson()) {
            $this->systemLogService->log(
                logType: 'page_view',
                action: 'providers_list_view',
                description: 'User viewed providers list',
                metadata: [
                    'tenant_id' => auth()->user()->tenant->id,
                    'providers_count' => $providers->total(),
                    'page' => $providers->currentPage(),
                ]
            );
        }

        return view('livewire.systems.dialer.providers-list', [
            'providers' => $providers,
            'agent' => $agent,
            'license' => $license,
        ]);
    }
}
