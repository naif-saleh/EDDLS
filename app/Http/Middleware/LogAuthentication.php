<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SystemLogService;
use Illuminate\Support\Facades\Auth;

class LogAuthentication
{
    protected $systemLogService;

    public function __construct(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Store the authentication state before the request
        $wasLoggedIn = Auth::check();
        $previousUser = Auth::user();

        // Process the request
        $response = $next($request);

        // Check authentication state after the request
        $isLoggedIn = Auth::check();
        $currentUser = Auth::user();

        // Log login events
        if (!$wasLoggedIn && $isLoggedIn) {
            $this->systemLogService->logLogin($currentUser, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        // Log logout events
        elseif ($wasLoggedIn && !$isLoggedIn && $previousUser) {
            $this->systemLogService->logLogout($previousUser, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
        // Log user switching (e.g., "Login as" functionality)
        elseif ($wasLoggedIn && $isLoggedIn && $previousUser && $currentUser && $previousUser->id !== $currentUser->id) {
            $this->systemLogService->log(
                logType: 'auth',
                action: 'user_switch',
                model: $currentUser,
                description: "Switched user from {$previousUser->email} to {$currentUser->email}",
                metadata: [
                    'previous_user_id' => $previousUser->id,
                    'previous_user_email' => $previousUser->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]
            );
        }

        return $response;
    }
} 