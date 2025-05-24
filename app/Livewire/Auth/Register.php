<?php

namespace App\Livewire\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Str;

#[Layout('components.layouts.auth')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $tenant = Tenant::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'slug' => Str::slug($validated['name']),
            'database_name' => Str::slug($validated['name']).'_tenant_db',
            'database_username' => 'devops',
            'database_password' => 774027237,
            'database_created' => false,
        ]);
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' =>  $validated['password'],
            'tenant_id' => $tenant->id
        ]);

        event(new Registered($user));

         // Auth::login($user);

         session()->flash('success', 'Your registration was successful. Please wait for admin approval.');

        return redirect()->route('login');
    }
}
