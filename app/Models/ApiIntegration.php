<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiIntegration extends Model
{

    protected $fillable = [
        'pbx_url',
        'client_id',
        'client_secret',
        'tenant_id',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

     /**
     * Get the tenant that owns the API integration.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
