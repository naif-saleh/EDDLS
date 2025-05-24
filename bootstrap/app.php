<?php


use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant.access' => \App\Http\Middleware\CheckTenantAccess::class,
            'only.admin' => \App\Http\Middleware\onlyAdmin::class,
            // 'verify.provider.tenant' => \App\Http\Middleware\VerifyProviderBelongsToTenant::class,
            'license.management' => \App\Http\Middleware\LicenseManagementMiddleware::class,
            'share.license' => \App\Http\Middleware\ShareLicenseDataMiddleware::class,
            'tenant.database' => \App\Http\Middleware\TenantDatabaseMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
 