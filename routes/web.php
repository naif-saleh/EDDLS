<?php

use App\Http\Controllers\SkippedNumbersReportController;
use App\Livewire\Admin\Dashboard\AdminStats;
use App\Livewire\Admin\Dashboard\AgentStats;
use App\Livewire\Admin\Licenses\LicenseContent;
use App\Livewire\Admin\Licenses\LicenseForm;
use App\Livewire\Admin\Licenses\LicenseForTenant;
use App\Livewire\Admin\Licenses\LicenseList;
use App\Livewire\Admin\Licenses\LicenseUpdate;
use App\Livewire\Admin\SystemLog\TenantLog;
use App\Livewire\Admin\Tenants\TenantList;
use App\Livewire\ApiIntegration\CradentialsForm;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TenantSettings;
use App\Livewire\Systems\Campaign\DialerCampaignDetails;
use App\Livewire\Systems\Campaign\DialerCampaignForm;
use App\Livewire\Systems\Campaign\DialerCampaignsList;
use App\Livewire\Systems\Campaign\DialerCampaignUpdate;
use App\Livewire\Systems\Campaign\DistributorCampaignsDetails;
use App\Livewire\Systems\Campaign\DistributorCampaignsForm;
use App\Livewire\Systems\Campaign\DistributorCampaignsList;
use App\Livewire\Systems\Dialer\DialerCallsReport;
use App\Livewire\Systems\Dialer\ProvidersList;
use App\Livewire\Systems\Distributor\AgentList;
use App\Livewire\Systems\Distributor\ProvidersList as DistributorProvidersList;
use App\Livewire\Systems\Distributor\DistributorCallsReport;
use App\Livewire\Systems\SkippedNumbers\DialerSkippedNumbers;
use App\Livewire\Systems\SkippedNumbers\DistributorSkippedNumbers;
use App\Livewire\Tenant\EmployeeEdit;
use App\Livewire\Tenant\EmployeeForm;
use App\Livewire\Tenant\EmployeeList;
use App\Livewire\Users\UserList;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if(auth()->check() && auth()->user()->isSuperAdmin()){
        return redirect()->route('admin.dashboard');
    }elseif(auth()->check() && auth()->user()->isTenantAdmin() || auth()->check() && auth()->user()->isAgent()){
        return redirect()->route('tenant.dashboard', ['tenant' => auth()->user()->tenant->slug]);
    } else{
        return redirect()->route('login');
    }
})->name('home');



Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->middleware(['only.admin', 'license.management'])->group(function () {
    // Admin Dashboard
    Route::get('dashboard', AdminStats::class)->name('dashboard');

    // Tenant Route
    Route::get('tenant-list', TenantList::class)->name('tenant.list');

    // Users Route
    Route::get('users-list', UserList::class)->name('user.list');

    // License Route
    Route::get('licesnse-list/', LicenseList::class)->name('license.list');
    Route::get('licesnse-form/{tenant_id}', LicenseForm::class)->name('license.form');
    Route::get('licesnse-content/{license_id}', LicenseContent::class)->name('license.content');
    Route::get('licesnse-update/{license_id}', LicenseUpdate::class)->name('license.update');

    // System Log
    Route::get('system-log-view', TenantLog::class)->name('system.log');

});



// License routes - use main database
Route::prefix('tenant/{tenant:slug}')->name('tenant.')->middleware(['auth', 'tenant.access', 'license.management'])->group(function () {
    // License View only
    Route::get('license-information', LicenseForTenant::class)->name('license.tenant');
});
// Tenant routes with tenant middleware
Route::prefix('tenant/{tenant:slug}')->name('tenant.')->middleware(['auth', 'tenant.access',  'tenant.database', 'share.license'])->group(function () {
    // Api Integration
    Route::get('/api-integration', CradentialsForm::class)->name('integration.form');

    // // License View
    // Route::get('licesnse-information', LicenseForTenant::class)->name('license.tenant');

    // System Log
    Route::get('system-log-view', TenantLog::class)->name('system.log');

    // Tenant Settings
    Route::get('settings', TenantSettings::class)->name('settings');

    // Manage Employees
    Route::get('manage-employees', EmployeeList::class)->name('employees.list');
    Route::get('manage-employees/create-employee', EmployeeForm::class)->name('employees.create');
    Route::get('manage-employees/edit-employee/{employee_id}', EmployeeEdit::class)->name('employees.edit');

    Route::get('/dashboard', AgentStats::class)->name('dashboard');
    // Dialer Routes
    Route::get('/dialer/providers', ProvidersList::class)->name('dialer.providers');
    Route::get('/dialer/provider/{provider}/campaigns-list', DialerCampaignsList::class)->name('dialer.provider.campaigns.list');
    Route::get('/dialer/provider/{provider}/campaign-create', DialerCampaignForm::class)->name('dialer.provider.campaigns.create');
    Route::get('/dialer/provider/{provider}/campaign/{campaign:slug}/contact', DialerCampaignDetails::class)->name('dialer.provider.campaign.contact');
    Route::get('/dialer/provider/{provider}/campaign/{campaign:slug}/edit-campaign', DialerCampaignUpdate::class)->name('dialer.provider.campaign.edit');
    Route::get('/dialer/skipped-numbers', DialerSkippedNumbers::class)->name('dialer.skipped.numbers');
    Route::get('/dialer/calls-report', DialerCallsReport::class)->name('dialer.calls.report');

    // Distributor Routes
    Route::get('/distributor/agents', AgentList::class)->name('distributor.agents');
    Route::get('/distributor/{agent}/providers', DistributorProvidersList::class)->name('distributor.providers');
    Route::get('/distributor/provider/{provider}/agent/{agent}/campaigns', DistributorCampaignsList::class)->name('distributor.provider.campaigns.list');
    Route::get('/distributor/provider/{provider}/agent/{agent}/campaign-create', DistributorCampaignsForm::class)->name('distributor.provider.campaigns.create');
    Route::get('/distributor/provider/{provider}/agent/{agent}/campaign/{campaign:slug}/contact', DistributorCampaignsDetails::class)->name('distributor.provider.campaign.contact');
    Route::get('/distributor/skipped-numbers', DistributorSkippedNumbers::class)->name('distributor.skipped.numbers');
    Route::get('/distributor/calls-report', DistributorCallsReport::class)->name('distributor.calls.report');

});

Route::prefix('api/skipped-numbers')->group(function () {
    Route::get('/by-provider', [SkippedNumbersReportController::class, 'getGroupedByProvider']);
    Route::get('/provider/{providerId}', [SkippedNumbersReportController::class, 'getProviderDetails']);
    Route::get('/summary', [SkippedNumbersReportController::class, 'getSummary']);

    // Download routes
    Route::get('/download', [SkippedNumbersReportController::class, 'downloadProviderReport'])->name('skipped-numbers.download');
    Route::get('/download/{providerId}', [SkippedNumbersReportController::class, 'downloadProviderReport'])->name('skipped-numbers.download.provider');
});
// Campaign Routes
//  Route::prefix('campaigns')->name('campaigns.')->group(function () {
//     Route::get('/', CampaignList::class)->name('index');
//     Route::get('/create', CampaignForm::class)->name('create');
//     Route::get('/{campaignId}/edit', CampaignForm::class)->name('edit');
//     Route::get('/{campaignId}/monitor', CampaignMonitor::class)->name('monitor');
//     Route::get('/{campaignId}/contacts', function ($campaignId) {
//         return view('campaigns.contacts', ['campaignId' => $campaignId]);
//     })->name('contacts');
// });

require __DIR__.'/auth.php';
