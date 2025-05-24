<?php

// app/Providers/RouteServiceProvider.php

namespace App\Providers;

use App\Models\Provider;
use App\Services\TenantService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            Route::bind('provider', function ($value) {
                $currentTenantId = auth()->user()->tenant_id;

                TenantService::setConnection(auth()->user()->tenant);
                return Provider::where('slug', $value)
                    ->where('tenant_id', $currentTenantId)
                    ->firstOrFail();
            });

        });

    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
