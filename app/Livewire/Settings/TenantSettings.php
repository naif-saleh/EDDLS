<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Masmerise\Toaster\Toaster;

class TenantSettings extends Component
{
    use WithFileUploads;

    // Properties
    public $timeStart;
    public $timeEnd;
    public $numberOfCalls;
    public $tenantLogo;
    public $autoCall = false;
    public $setting;

    protected $rules = [
        'timeStart' => 'required',
        'timeEnd' => 'required|after:timeStart',
        'numberOfCalls' => 'required|integer|min:1',
        'tenantLogo' => 'nullable|file|mimes:jpeg,png,jpg,csv|max:1024',
        'autoCall' => 'boolean',
    ];

    protected $messages = [
        'timeStart.required' => 'Please set the operating start time.',
        'timeEnd.required' => 'Please set the operating end time.',
        'timeEnd.after' => 'End time must be after start time.',
        'numberOfCalls.required' => 'Number of calls is required.',
        'numberOfCalls.integer' => 'Number of calls must be a whole number.',
        'numberOfCalls.min' => 'Number of calls must be at least 1.',
         'tenantLogo.max' => 'Logo size must not exceed 1MB.',
    ];

    public function mount()
    {
        // Get current tenant
        $tenant = Auth::user()->tenant;

        // Load existing settings if available
        $this->setting = Setting::where('tenant_id', $tenant->id)->first();

        if ($this->setting) {
            $this->timeStart = $this->setting->start_time ? $this->setting->start_time->format('H:i') : null;
            $this->timeEnd = $this->setting->end_time ? $this->setting->end_time->format('H:i') : null;
            $this->numberOfCalls = $this->setting->calls_at_time;
            $this->autoCall = $this->setting->auto_call;
        }
    }

    public function storeSettings()
    {
        $this->validate();

        $tenant = Auth::user()->tenant;

        // Process logo if uploaded
        $logoPath = null;
        if ($this->tenantLogo) {
            $logoPath = $this->tenantLogo->store('tenant-logos', 'public');
        }

        // Update or create settings
        $settings = [
            'tenant_id' => $tenant->id,
            'start_time' => $this->timeStart,
            'end_time' => $this->timeEnd,
            'calls_at_time' => $this->numberOfCalls,
            'auto_call' => $this->autoCall,
        ];

        // Only update logo if a new one was uploaded
        if ($logoPath) {
            $settings['logo'] = $logoPath;

            // Delete old logo if exists
            if ($this->setting && $this->setting->logo) {
                Storage::disk('public')->delete($this->setting->logo);
            }
        }

        if ($this->setting) {
            $this->setting->update($settings);
            Toaster::success('Tenant settings updated successfully!');
        } else {
            Setting::create($settings);
            Toaster::success('Tenant settings created successfully!');
        }

        // Refresh data
        $this->setting = Setting::where('tenant_id', $tenant->id)->first();
    }
    public function render()
    {
        return view('livewire.settings.tenant-settings');
    }
}
