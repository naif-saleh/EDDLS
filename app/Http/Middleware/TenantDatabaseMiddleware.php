<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Masmerise\Toaster\Toaster;


class TenantDatabaseMiddleware
{
    /**
 * Handle tenant database connection
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \Closure  $next
 * @return mixed
 */
public function handle(Request $request, Closure $next)
{
    $tenant = $request->route('tenant');
    
    // Check if tenant is valid
    if (!$tenant instanceof Tenant) {
        Toaster::error('Invalid tenant');
        return response()->view('database_not_found', [
            'errorMessage' => 'Database not found for this tenant',
            'tenant' => $tenant
        ], 404);
    }
    
    // Check if database is created for this tenant
    if (!$tenant->database_created) {
        Toaster::error('Tenant database not found');
        return response()->view('database_not_found', [
            'errorMessage' => 'Database not found for this tenant',
            'tenant' => $tenant
        ], 404);
    }
    
    // Set the database connection for the tenant
    Config::set('database.connections.tenant', [
        'driver' => 'mysql',
        'host' => Config::get('database.connections.mysql.host'),
        'port' => Config::get('database.connections.mysql.port'),
        'database' => $tenant->database_name,
        'username' => $tenant->database_username,
        'password' => $tenant->database_password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
    ]);

    // Set the default connection to tenant
    Config::set('database.default', 'tenant');
    
    // Clear the existing connection if it was established
    if (DB::connection()->getDatabaseName() !== $tenant->database_name) {
        DB::purge('tenant');
    }
    
    // Reconnect to the database with the new configuration
    DB::reconnect('tenant');
    
    return $next($request);
}
} 