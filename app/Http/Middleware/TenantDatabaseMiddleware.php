<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\Toaster;

class TenantDatabaseMiddleware
{
    /**
     * Handle tenant database connection
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->route('tenant');
        
      //  Check if tenant is valid
        if (!$tenant instanceof Tenant) {
            Toaster::error('Invalid tenant');
            return response()->view('database_not_found', [
                'errorMessage' => 'Database not found for this tenant',
                'tenant' => $tenant
            ], 404);
        }

        // Check if database is created for this tenant
        if (!$tenant->database_created) {
            // dd($tenant);
            Toaster::error('Tenant database not found');
            return response()->view('database_not_found', [
                'errorMessage' => 'Database not found for this tenant',
                'tenant' => $tenant
            ], 404);
        }

        if ($tenant instanceof Tenant) {
            DB::purge('tenant');
            // Configure the tenant connection
            Config::set('database.connections.tenant.database', $tenant->database_name);

            // Clear any existing tenant connection
            if (DB::connection()->getDatabaseName() !== $tenant->database_name) {
                DB::purge('mysql');
            }

            // Set the default connection to tenant
            Config::set('database.default', 'tenant');
            // Reconnect to the database with the new configuration
            DB::reconnect('tenant');
            // dd($tenant);
             // Store tenant in container for later use
        app()->instance('current_tenant', $tenant);
        app()->instance('current_tenant_id', $tenant->id);
        }



        return $next($request);
    }
}
