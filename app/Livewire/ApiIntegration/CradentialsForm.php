<?php

namespace App\Livewire\ApiIntegration;

use App\Models\ApiIntegration;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class CradentialsForm extends Component
{
    public $pbxUrl;

    public $clientId;

    public $ClientSecret;

    public $tenant;

    public $cradentials = [];

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

        ApiIntegration::updateOrCreate(
            ['tenant_id' => $this->tenant],
            [
                'pbx_url' => $this->pbxUrl,
                'client_id' => $this->clientId,
                'client_secret' => $this->ClientSecret,
            ]
        );

        if ($this->cradentials) {
            Toaster::success('Credentials Updated Successfully');
        } else {
            Toaster::success('Credentials Created Successfully');
        }

    }

    private function authorizeTenant()
    {
        if ($this->tenant !== $this->tenant) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function render()
    {

        return view('livewire.api-integration.cradentials-form');
    }
}
