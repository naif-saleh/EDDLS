<?php

namespace App\Livewire\Admin\Licenses;

use App\Services\LicenseService;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class LicenseUsage extends Component
{
    public $licenseKey = '';
    public $licenseStatus = [];
    public $licenseUsage = [];
    public $isActivating = false;

    public function mount(LicenseService $licenseService)
    {
        $this->refreshLicenseData($licenseService);
    }

    public function refreshLicenseData(LicenseService $licenseService)
    {
        // Get license status
        $this->licenseStatus = $licenseService->getExpirationStatus();

        // Get license usage
        $this->licenseUsage = $licenseService->getLicenseUsage();
    }

    public function activateLicense(LicenseService $licenseService)
    {
        $this->isActivating = true;

        // Validate license key format
        if (empty($this->licenseKey) || strlen($this->licenseKey) < 10) {
            Toaster::error('Please enter a valid license key');
            $this->isActivating = false;
            return;
        }

        // Attempt to activate the license
        $result = $licenseService->activateLicense($this->licenseKey);

        if ($result['valid']) {
            Toaster::success('License activated successfully');
            $this->licenseKey = ''; // Clear the input
            $this->refreshLicenseData($licenseService);
        } else {
            Toaster::error($result['message']);
        }

        $this->isActivating = false;
    }

    public function deactivateLicense(LicenseService $licenseService, $licenseId)
    {
        // Confirm with the user before deactivation
        if (!$this->confirm('Are you sure you want to deactivate this license?')) {
            return;
        }

        $license = $licenseService->deactivateLicense($licenseId);

        if ($license) {
            Toaster::success('License deactivated successfully');
            $this->refreshLicenseData($licenseService);
        } else {
            Toaster::error('Failed to deactivate license');
        }
    }

    public function getLicenseStatusClass()
    {
        if (!$this->licenseStatus['has_license'] || !$this->licenseStatus['is_valid']) {
            return 'bg-red-100 text-red-800';
        }

        if ($this->licenseStatus['expiring_soon']) {
            return 'bg-yellow-100 text-yellow-800';
        }

        return 'bg-green-100 text-green-800';
    }

    public function getLicenseStatusText()
    {
        if (!$this->licenseStatus['has_license']) {
            return 'No Active License';
        }

        if (!$this->licenseStatus['is_valid']) {
            return 'License Expired';
        }

        if ($this->licenseStatus['expiring_soon']) {
            return "Expiring Soon ({$this->licenseStatus['days_remaining']} days)";
        }

        return 'Active';
    }

    
    public function render()
    {
        return view('livewire.admin.licenses.license-usage');
    }
}
