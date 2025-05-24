<?php

 namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class ShareLicenseDataMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            // Get license data from main database
            $license = DB::connection('mysql')
                ->table('licenses')
                ->where('tenant_id', $tenant->id)
                ->first();

            // Share license data with the view
            view()->share('license', $license);
        }

        return $next($request);
    }
}
