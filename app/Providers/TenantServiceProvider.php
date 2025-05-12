<?php
// app/Providers/TenantServiceProvider.php
namespace App\Providers;

use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Register tenant resolver
        $this->app->singleton('tenant', function ($app) {
            if (Auth::check() && Auth::user()->tenant_id) {
                return Tenant::find(Auth::user()->tenant_id);
            }
            return null;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // // Share tenant with all views
        // View::composer('*', function ($view) {
        //     $currentTenant = app('current_tenant') ?? app('tenant');
        //     $view->with('currentTenant', $currentTenant);
        // });

        // Custom Route pattern for tenant URLs
        Route::pattern('tenant', '[a-z0-9-]+');

        // Register tenant route binding with custom resolution
        Route::bind('tenant', function ($value) {
            $tenant = Tenant::where('slug', $value)->first();

            if (!$tenant && Auth::check() && !Auth::user()->isSuperAdmin()) {
                // If tenant not found in URL but user is logged in, try to use their tenant
                $tenant = Tenant::find(Auth::user()->tenant_id);
            }

            if (!$tenant) {
                abort(404, 'Tenant not found');
            }

            return $tenant;
        });
    }
}
