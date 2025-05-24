<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class LicenseManagementMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Ensure we're using the main database for license operations
        Config::set('database.default', 'mysql');

        // If we were previously using a tenant connection, purge it
        if (DB::getDefaultConnection() !== 'mysql') {
            DB::purge('tenant');
            DB::reconnect('mysql');
        }

        return $next($request);
    }
}
