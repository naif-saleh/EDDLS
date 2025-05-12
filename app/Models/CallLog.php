<?php


// app/Models/CallLog.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'campaign_id',
        'provider_id',
        'agent_id',
        'call_id',
        'call_type',
        'call_duration',
        'call_status',
        'recording_url',
        'notes',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}
