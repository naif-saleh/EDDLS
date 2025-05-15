<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'email',
        'phone',
        'status',
        'api_key',
    ];

    /**
     * Get the users associated with the tenant
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function providers()
    {
        return $this->hasMany(Provider::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }


    public function setting()
    {
        return $this->hasOne(Setting::class);
    }
    
    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function licenses()
    {
        return $this->hasMany(License::class);
    }

    public function activeLicense()
    {
        return $this->licenses()
            ->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->latest()
            ->first();
    }

    protected static function booted()
    {
        static::creating(function ($tenant) {
            // Ensure slug is created if not provided
            if (empty($tenant->slug)) {
                $tenant->slug = Str::slug($tenant->name);
            }

            // Ensure slug is unique
            $originalSlug = $tenant->slug;
            $count = 1;

            while (static::where('slug', $tenant->slug)->exists()) {
                $tenant->slug = $originalSlug . '-' . $count++;
            }

            // Generate API key if not provided
            if (empty($tenant->api_key)) {
                $tenant->api_key = Str::random(32);
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to active tenants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if tenant is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get tenant's dashboard URL
     *
     * @return string
     */
    public function dashboardUrl()
    {
        return route('tenant.dashboard', ['tenant' => $this->slug]);
    }
}
