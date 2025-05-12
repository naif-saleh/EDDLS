<?php

// app/Models/Provider.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'extension',
        'tenant_id',
        'status',
        'provider_type',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function agents()
    {
        return $this->hasMany(Agent::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    public function skippedNumbers()
    {
        return $this->hasMany(SkippedNumber::class);
    }



    protected static function booted()
    {
        static::creating(function ($provider) {
            $provider->slug = Str::slug($provider->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
