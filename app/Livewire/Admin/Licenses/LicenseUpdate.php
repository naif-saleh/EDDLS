<?php

namespace App\Livewire\Admin\Licenses;

use App\Models\License;
use Livewire\Component;
use Illuminate\Support\Str;
use Masmerise\Toaster\Toaster;

class LicenseUpdate extends Component
{
    public $license_key;

    public $valid_from;

    public $valid_until;

    public $max_campaigns;

    public $max_agents;

    public $max_providers;

    public $max_dist_calls;

    public $max_dial_calls;

    public $max_contacts_per_campaign;

    public $is_active = true;

    public $license = [];

    public $isGeneratingKey = false;

    public function mount($license_id)
    {
        $this->license = License::find($license_id);

        $this->fill([
            'license_key' => $this->license->license_key,
            'valid_from' => optional($this->license->valid_from)->format('Y-m-d'),
            'valid_until' => optional($this->license->valid_until)->format('Y-m-d'),
            'max_campaigns' => $this->license->max_campaigns,
            'max_agents' => $this->license->max_agents,
            'max_providers' => $this->license->max_providers,
            'max_dist_calls' => $this->license->max_dist_calls,
            'max_dial_calls' => $this->license->max_dial_calls,
            'max_contacts_per_campaign' => $this->license->max_contacts_per_campaign,
            'is_active' => $this->license->is_active,
        ]);
    }


    public function generateLicenseKey()
    {
        $this->isGeneratingKey = true;

        // Generate a random license key
        $this->license_key = Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8) . '-' . Str::random(8);

        $this->isGeneratingKey = false;
    }

    public function update()
    {
        $validatedData = $this->validate([
            // 'tenant_id' => 'required|exists:tenants,id',
            'license_key' => 'required|string|min:16|max:64',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'max_campaigns' => 'required|integer|min:0',
            'max_agents' => 'required|integer|min:0',
            'max_providers' => 'required|integer|min:0',
            'max_dist_calls' => 'required|integer|min:0',
            'max_dial_calls' => 'required|integer|min:0',
            'max_contacts_per_campaign' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);



        $this->license->update([
                'license_key' => $this->license_key,
                'valid_from' => $this->valid_from,
                'valid_until' => $this->valid_until,
                'max_campaigns' => $this->max_campaigns,
                'max_agents' => $this->max_agents,
                'max_providers' => $this->max_providers,
                'max_dist_calls' => $this->max_dist_calls,
                'max_dial_calls' => $this->max_dial_calls,
                'max_contacts_per_campaign' => $this->max_contacts_per_campaign,
                'is_active' => $this->is_active,
            ]);

            // Reset form
            $this->reset([
                'license_key',
                'max_campaigns',
                'max_agents',
                'max_providers',
                'max_dist_calls',
                'max_dial_calls',
                'max_contacts_per_campaign',
            ]);


            Toaster::success("License Updated Successfully");

            // Redirect to licenses list
            return redirect()->route('admin.license.list');


    }

    public function render()
    {
        return view('livewire.admin.licenses.license-update');
    }
}
