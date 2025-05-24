<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkippedNumber extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

     protected $connection = 'tenant';
    protected $fillable = [
        'phone_number',
        'provider_id',
        'agent_id',
        'campaign_id',
        'batch_id',
        'tenant_id',
        'skip_reason',
        'file_name',
        'row_number',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'raw_data' => 'json',
    ];

    /**
     * Get the tenant that owns this skipped number.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the provider that this number was skipped for.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the agent that this number was skipped for.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the campaign that this number was skipped for.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

     // Get provider information from raw data
     public function getProviderNameAttribute()
     {
         if (is_array($this->raw_data) && isset($this->raw_data['provider_name'])) {
             return $this->raw_data['provider_name'];
         }

         if (is_string($this->raw_data)) {
             $decoded = json_decode($this->raw_data, true);
             return $decoded['provider_name'] ?? 'Unknown';
         }

         return 'Unknown';
     }

     public function getProviderExtensionAttribute()
     {
         if (is_array($this->raw_data) && isset($this->raw_data['provider_extension'])) {
             return $this->raw_data['provider_extension'];
         }

         if (is_string($this->raw_data)) {
             $decoded = json_decode($this->raw_data, true);
             return $decoded['provider_extension'] ?? 'Unknown';
         }

         return 'Unknown';
     }

     // Helper scopes for common queries
     public function scopeByProvider($query, $providerId)
     {
         return $query->where('provider_id', $providerId);
     }

     public function scopeWithoutAgent($query)
     {
         return $query->whereNull('agent_id');
     }

     public function scopeByReason($query, $reason)
     {
         return $query->where('skip_reason', $reason);
     }

     public function scopeByBatch($query, $batchId)
     {
         return $query->where('batch_id', $batchId);
     }

     // Group by provider with count
     public static function countByProvider()
     {
         return self::select('provider_id')
             ->selectRaw('COUNT(*) as count')
             ->whereNull('agent_id')
             ->groupBy('provider_id')
             ->get();
     }

     // Get skipped data summary
     public static function getSummary()
     {
         return [
             'total' => self::count(),
             'unassigned' => self::whereNull('agent_id')->count(),
             'by_reason' => self::select('skip_reason')
                 ->selectRaw('COUNT(*) as count')
                 ->groupBy('skip_reason')
                 ->get(),
             'by_provider' => self::countByProvider(),
         ];
     }
}
