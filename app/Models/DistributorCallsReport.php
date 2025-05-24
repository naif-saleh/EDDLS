<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistributorCallsReport extends Model
{
    protected $connection = 'tenant';
    protected $fillable = [
        'tenant_id',
        'date_time',
        'agent',
        'provider',
        'campaign',
        'phone_number',
        'call_status',
        'dialing_duration',
        'talking_duration',
        'call_at',
    ];
}
