<?php

namespace App\Livewire\ApiIntegration;

use App\Models\ApiIntegration;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use App\Services\SystemLogService;

class CradentialsForm extends Component
{
    public $pbxUrl;

    public $clientId;

    public $ClientSecret;

    public $tenant;

    public $cradentials = [];

    protected $systemLog;

    public function boot(SystemLogService $systemLog)
    {
        $this->systemLog = $systemLog;
    }

    public function mount()
    {
        $this->tenant = auth()->user()->tenant_id;
        $this->cradentials = ApiIntegration::where('tenant_id', $this->tenant)->first();

        if ($this->cradentials) {
            $this->pbxUrl = $this->cradentials->pbx_url;
            $this->clientId = $this->cradentials->client_id;
            $this->ClientSecret = $this->cradentials->client_secret;
        }
    }

    public function makeIntegration()
    {
        $this->validate([
            'pbxUrl' => 'required|url',
            'clientId' => 'required|string|max:255',
            'ClientSecret' => 'required|string|max:255',
        ]);

        $this->authorizeTenant();

        $data = [
            'pbx_url' => $this->pbxUrl,
            'client_id' => $this->clientId,
            'client_secret' => $this->ClientSecret,
        ];

        $isUpdate = $this->cradentials !== null;
        $previousData = $isUpdate ? $this->cradentials->toArray() : null;

        $integration = ApiIntegration::updateOrCreate(
            ['tenant_id' => $this->tenant],
            $data
        );

        // Log the action with correct parameter order
        $this->systemLog->log(
            logType: $isUpdate ? 'info' : 'success',
            action: $isUpdate ? 'update' : 'create',
            model: $integration,
            description: $isUpdate 
                ? 'API Integration credentials updated' 
                : 'API Integration credentials created',
            previousData: $previousData,
            newData: array_merge($data, ['tenant_id' => $this->tenant])
        );

        if ($isUpdate) {
            Toaster::success('Credentials Updated Successfully');
        } else {
            Toaster::success('Credentials Created Successfully');
        }
    }

    private function authorizeTenant()
    {
        if ($this->tenant !== auth()->user()->tenant_id) {
            // Log unauthorized access with correct parameter order
            $this->systemLog->log(
                logType: 'error',
                action: 'unauthorized_access',
                model: null,
                description: 'Unauthorized attempt to modify API credentials',
                metadata: ['attempted_tenant_id' => $this->tenant]
            );
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {
        return view('livewire.api-integration.cradentials-form');
    }
}
