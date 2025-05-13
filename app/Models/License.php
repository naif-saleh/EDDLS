<?php
// app/Models/License.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'license_key',
        'valid_from',
        'valid_until',
        'max_campaigns',
        'max_agents',
        'max_providers',
        'max_dist_calls',
        'max_dial_calls',
        'max_contacts_per_campaign',
        'is_active',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isValid()
    {
        return $this->is_active &&
               Carbon::now()->between($this->valid_from, $this->valid_until);
    }

    public function getDaysRemaining()
    {
        return Carbon::now()->diffInDays($this->valid_until, false);
    }

    public function hasReachedCampaignLimit()
    {
        return $this->tenant->campaigns()->count() >= $this->max_campaigns;
    }

    public function hasReachedAgentLimit()
    {
        return $this->tenant->agents()->count() >= $this->max_agents;
    }

    public function hasReachedProviderLimit()
    {
        return $this->tenant->providers()->count() >= $this->max_providers;
    }

    



}
