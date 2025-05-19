<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use App\Traits\TracksStatusChanges;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LogsActivity, TracksStatusChanges;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role',
        'status'

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }


    /**
     * Get the tenant that the user belongs to
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user is a super admin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    /**
     * Check if user is a tenant admin
     */
    public function isTenantAdmin()
    {
        return $this->role === 'tenant_admin';
    }

    /**
     * Check if user is an agent
     */
    public function isAgent()
    {
        return $this->role === 'agent';
    }

    /**
     * Check if user is a provider
     */
    public function isProvider()
    {
        return $this->role === 'provider';
    }

    /**
     * Get related agent model if this user is an agent
     */
    public function agent()
    {
        if ($this->role !== 'agent' || !$this->related_id) {
            return null;
        }

        return Agent::find($this->related_id);
    }

    /**
     * Get related provider model if this user is a provider
     */
    public function provider()
    {
        if ($this->role !== 'provider' || !$this->related_id) {
            return null;
        }

        return Provider::find($this->related_id);
    }

    /**
     * Check if the user can access a specific campaign
     */
    public function canAccessCampaign(Campaign $campaign)
    {
        // Super admin can access everything
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Must be in the same tenant
        if ($this->tenant_id !== $campaign->tenant_id) {
            return false;
        }

        // Tenant admins can access all campaigns in their tenant
        if ($this->isTenantAdmin()) {
            return true;
        }

        // Providers can access campaigns assigned to them
        if ($this->isProvider() && $this->related_id === $campaign->provider_id) {
            return true;
        }

        // Agents can access distributor campaigns assigned to them
        if ($this->isAgent() &&
            $campaign->campaign_type === 'distributor' &&
            $this->related_id === $campaign->agent_id) {
            return true;
        }

        return false;
    }
}
