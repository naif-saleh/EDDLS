<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DialerCallsReport extends Model
{
    protected $fillable = [
        'call_id',
        'tenant_id',
        'date_time',
        'provider',
        'campaign',
        'phone_number',
        'call_status',
        'dialing_duration',
        'talking_duration',
        'call_at',
    ];
}
