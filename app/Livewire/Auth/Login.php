<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $user = Auth::user();

        // 🔐 Allow SuperAdmin immediately
        if (! $user->isSuperAdmin()) {
            // 🔐 If NOT super admin, check tenant admin approval
            // if (!$user->isTenantAdmin() || $user->role != 'agent')  {
            //     Auth::logout();
            //     session()->flash('success', 'You are not approved . Please call us.');
            //     $this->redirect(route('login'), navigate: true);
            //     return;
            // }

            if($user->tenant->status != 'active'){
                Auth::logout();
                session()->flash('success', 'Your tenant is not activated . Please call us.');
                $this->redirect(route('login'), navigate: true);
                return;
            }
        }

        // ✅ Approved and allowed → redirect
        [$routeName, $parameters] = $this->getRedirectRouteForUser($user);
        $url = route($routeName, $parameters, absolute: false);

        $this->redirectIntended(default: $url, navigate: true);
    }


    /**
     * Determine the appropriate redirect route for the user based on their role.
     */
    protected function getRedirectRouteForUser($user): array
    {
        if ($user->isSuperAdmin()) {
            return ['admin.dashboard', []];
        }

        if ($user->isTenantAdmin()) {
            return ['tenant.dashboard', ['tenant' => $user->tenant->slug]];
        }

        // Should not get here — handled in login()
        return ['dashboard', []];
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
