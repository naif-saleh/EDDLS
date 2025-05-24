<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Masmerise\Toaster\Toaster;
use App\Services\SystemLogService;
use App\Services\TenantService;

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

    protected $systemLog;

    public function boot(SystemLogService $systemLog)
    {
        $this->systemLog = $systemLog;
    }

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

    protected function getChangesDescription($oldData, $newData)
    {
        $changes = [];
        
        if ($oldData['start_time'] !== $newData['start_time']) {
            $changes[] = "Operating hours start: {$oldData['start_time']} → {$newData['start_time']}";
        }
        
        if ($oldData['end_time'] !== $newData['end_time']) {
            $changes[] = "Operating hours end: {$oldData['end_time']} → {$newData['end_time']}";
        }
        
        if ($oldData['calls_at_time'] !== $newData['calls_at_time']) {
            $changes[] = "Concurrent calls: {$oldData['calls_at_time']} → {$newData['calls_at_time']}";
        }
        
        if ($oldData['auto_call'] !== $newData['auto_call']) {
            $oldValue = $oldData['auto_call'] ? 'Enabled' : 'Disabled';
            $newValue = $newData['auto_call'] ? 'Enabled' : 'Disabled';
            $changes[] = "Auto call: {$oldValue} → {$newValue}";
        }

        return !empty($changes) ? implode("\n", $changes) : 'No changes detected';
    }

    public function mount()
    {
        $tenant = Auth::user()->tenant;
        
        // Ensure tenant connection is properly set up
        TenantService::setConnection($tenant);

        // Load existing settings - use consistent connection approach
        $this->setting = Setting::on('tenant')->where('tenant_id', $tenant->id)->first();

        if ($this->setting) {
            $this->timeStart = $this->setting->start_time ? $this->setting->start_time->format('H:i') : null;
            $this->timeEnd = $this->setting->end_time ? $this->setting->end_time->format('H:i') : null;
            $this->numberOfCalls = $this->setting->calls_at_time;
            $this->autoCall = $this->setting->auto_call;
        }
    }

    public function storeSettings()
    {
        $tenant = Auth::user()->tenant;
        
        // Set tenant connection before any database operations
        TenantService::setConnection($tenant);
        
        $this->validate();

        $isUpdate = $this->setting !== null;
        
        // Prepare settings data
        $settings = [
            'tenant_id' => $tenant->id,
            'start_time' => $this->timeStart,
            'end_time' => $this->timeEnd,
            'calls_at_time' => $this->numberOfCalls,
            'auto_call' => $this->autoCall,
        ];

        // Process logo if uploaded
        $logoPath = null;
        if ($this->tenantLogo) {
            $logoPath = $this->tenantLogo->store('tenant-logos', 'public');
            $settings['logo'] = $logoPath;

            // Delete old logo if exists
            if ($this->setting && $this->setting->logo) {
                $oldLogoPath = $this->setting->logo;
                Storage::disk('public')->delete($oldLogoPath);
                
                // Log logo change with clear before/after values
                $this->systemLog->log(
                    logType: 'info',
                    action: 'logo_update',
                    model: $this->setting,
                    description: "Logo updated: {$oldLogoPath} → {$logoPath}",
                    previousData: ['logo' => $oldLogoPath],
                    newData: ['logo' => $logoPath]
                );
            }
        }

        if ($isUpdate) {
            $previousData = [
                'start_time' => $this->setting->start_time ? $this->setting->start_time->format('H:i') : 'Not set',
                'end_time' => $this->setting->end_time ? $this->setting->end_time->format('H:i') : 'Not set',
                'calls_at_time' => $this->setting->calls_at_time,
                'auto_call' => $this->setting->auto_call,
            ];

            // Force the model to use the tenant connection for update
            $this->setting->setConnection('tenant');
            $this->setting->update($settings);
            
            // Log settings update with detailed changes
            $this->systemLog->log(
                logType: 'info',
                action: 'update',
                model: $this->setting,
                description: $this->getChangesDescription($previousData, $settings),
                previousData: $previousData,
                newData: $settings
            );

            Toaster::success('Tenant settings updated successfully!');
        } else {
            // Create using tenant connection
            $setting = Setting::on('tenant')->create($settings);
            $this->setting = $setting;
            
            // Log settings creation with initial values
            $this->systemLog->log(
                logType: 'success',
                action: 'create',
                model: $setting,
                description: "Initial settings created:\n" .
                    "Operating hours: {$this->timeStart} - {$this->timeEnd}\n" .
                    "Concurrent calls: {$this->numberOfCalls}\n" .
                    "Auto call: " . ($this->autoCall ? 'Enabled' : 'Disabled'),
                newData: $settings
            );

            Toaster::success('Tenant settings created successfully!');
        }

        // Refresh data using tenant connection
        $this->setting = Setting::on('tenant')->where('tenant_id', $tenant->id)->first();
    }

    public function render()
    {
        return view('livewire.settings.tenant-settings');
    }
}