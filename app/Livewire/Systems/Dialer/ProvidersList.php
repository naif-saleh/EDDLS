<?php

namespace App\Livewire\Systems\Dialer;

use App\Models\Agent;
use App\Models\License;
use App\Models\Provider;
use App\Services\LicenseService;
use App\Services\SystemLogService;
use App\Services\TenantService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
    public $license;
    public $tenant;

    public function mount($tenant = null)
    {
        if (!$tenant) {
            $tenant = request()->route('tenant');
        }

        $this->tenant = $tenant;

        if ($tenant) {
            $this->license = DB::connection('mysql')
                ->table('licenses')
                ->where('tenant_id', $tenant->id)
                ->first();
        }
    }

    public function boot(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    protected function getLicenseService()
    {
        return new LicenseService;
    }

    public function updatingSearch()
    {
        $this->resetPage();

        $this->systemLogService->log(
            logType: 'search',
            action: 'provider_search',
            description: 'User searched for providers',
            metadata: ['search_term' => $this->search]
        );
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

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

    public function createProvider()
    {
        TenantService::setConnection(auth()->user()->tenant);

        $licenseService = $this->getLicenseService();

        if (! $licenseService->validProvidersCount(auth()->user()->tenant->id)) {
            Toaster::warning('License Providers limit reached. Please contact support.');
            return;
        }

        $originalConnection = DB::getDefaultConnection();

        try {
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
            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }

        DB::setDefaultConnection($originalConnection);

        $baseSlug = Str::slug($this->providerName);
        $slug = $baseSlug;
        $count = 1;

        while (Provider::on('tenant')->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
        }

        $provider = Provider::on('tenant')->create([
            'name' => $this->providerName,
            'extension' => $this->providerExtension,
            'tenant_id' => auth()->user()->tenant->id,
            'status' => $this->providerStatus,
            'slug' => $slug,
            'provider_type' => 'dialer',
        ]);

        $this->systemLogService->logCreate(
            model: $provider,
            description: "Created new provider: {$provider->name}"
        );

        Toaster::success('Provider is Created Successfully');

        return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
    }

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

            $this->dispatch('open-modal');

            $this->systemLogService->log(
                logType: 'ui_action',
                action: 'open_edit_modal',
                model: $provider,
                description: "Opened edit modal for provider: {$provider->name}"
            );
        }
    }

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
            $originalData = $provider->getOriginal();

            $provider->update([
                'name' => $this->providerName,
                'extension' => $this->providerExtension,
                'status' => $this->providerStatus,
            ]);

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

            $this->systemLogService->log(
                logType: 'error',
                action: 'provider_not_found',
                description: "Attempted to update non-existent provider ID: {$this->editingProviderId}"
            );

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        }
    }

    public function toggleProviderStatus($id, $isChecked)
    {
        TenantService::setConnection(auth()->user()->tenant);
        $provider = Provider::on('tenant')->find($id);

        if (! $provider) {
            Toaster::error('Provider not found.');
            $this->systemLogService->log(
                logType: 'error',
                action: 'provider_not_found',
                description: "Attempted to toggle status of non-existent provider ID: {$id}"
            );

            return;
        }

        $originalStatus = $provider->status;
        $newStatus = $isChecked ? 'active' : 'inactive';

        $provider->update([
            'status' => $newStatus,
        ]);

        $this->systemLogService->log(
            logType: 'status_change',
            action: 'provider_status_changed',
            model: $provider,
            description: 'Provider status changed',
            previousData: ['status' => $originalStatus],
            newData: ['status' => $newStatus]
        );

        Toaster::success('Provider status updated successfully.');
    }

    public function confirmDelete($providerId)
    {
        $this->confirmingDeleteId = $providerId;
        TenantService::setConnection(auth()->user()->tenant);
        $provider = Provider::on('tenant')->find($providerId);

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
        TenantService::setConnection(auth()->user()->tenant);

        $provider = Provider::on('tenant')->find($this->confirmingDeleteId);

        if ($provider) {
            $this->systemLogService->logDelete(
                model: $provider,
                description: "Deleted provider: {$provider->name}"
            );
            $provider->delete();

            Toaster::success('Provider deleted successfully.');

            $licenseService = $this->getLicenseService();
            $licenseService->icrementProvidersCount(auth()->user()->tenant_id);

            return redirect()->route('tenant.dialer.providers', ['tenant' => auth()->user()->tenant->slug]);
        } else {
            Toaster::error('Provider not found.');

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
        TenantService::setConnection(auth()->user()->tenant);

        $query = Provider::on('tenant')->where('tenant_id', auth()->user()->tenant->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('extension', 'like', '%'.$this->search.'%')
                  ->orWhere('status', 'like', '%'.$this->search.'%');
            });
        }

        $query->orderBy($this->sortField, $this->sortDirection);
        $providers = $query->paginate($this->perPage);

        $agent = Agent::on('tenant')
            ->where('slug', request()->route('agent'))
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first() ?? Agent::on('tenant')->where('tenant_id', auth()->user()->tenant_id)->first();

        $license = License::on('tenant')->where('tenant_id', auth()->user()->tenant_id)->first();

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
