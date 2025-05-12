<?php
// app/Http/Middleware/CheckTenantAccess.php
namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $tenantParam
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $tenantParam = 'tenant')
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super Admins bypass tenant restriction
        if ($user->isSuperAdmin()) {
            // Still set current tenant context from route for super admins
            $routeTenant = $request->route($tenantParam);
            if ($routeTenant instanceof Tenant) {
                app()->instance('current_tenant', $routeTenant);
                app()->instance('current_tenant_id', $routeTenant->id);
            }
            return $next($request);
        }

        // Check if user has a tenant_id
        if (!$user->tenant_id) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Account not properly configured. Please contact support.');
        }

        // Get tenant from route binding (e.g., {tenant:slug})
        $routeTenant = $request->route($tenantParam);

        // If tenant not found in route, try to resolve from user's tenant
        if (!$routeTenant instanceof Tenant) {
            // Get user's tenant
            $userTenant = Tenant::find($user->tenant_id);

            if (!$userTenant) {
                abort(404, 'Tenant not found.');
            }

            // Create new route parameters with the user's tenant inserted
            $parameters = $request->route()->parameters();
            $parameters[$tenantParam] = $userTenant;

            // Update the route parameters
            $request->route()->setParameter($tenantParam, $userTenant);
            $routeTenant = $userTenant;
        }

        // Compare user tenant_id with route tenant id
        if ($user->tenant_id !== $routeTenant->id) {
            abort(403, 'Access denied. You do not belong to this tenant.');
        }

        // Share tenant context globally so it's available throughout the application
        app()->instance('current_tenant', $routeTenant);
        app()->instance('current_tenant_id', $routeTenant->id);

        return $next($request);
    }



}
