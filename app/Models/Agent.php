<?php

// app/Models/Agent.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'extension',
        'tenant_id',
        'provider_id',
        'status',
        'email',
        'ContactImage',
        'CurrentProfileName',
        'QueueStatus',
        'three_cx_user_id'
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
