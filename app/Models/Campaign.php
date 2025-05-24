<?php

// app/Models/Campaign.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $connection = 'tenant';
    protected $fillable = [
        'slug',
        'name',
        'tenant_id',
        'provider_id',
        'agent_id',
        'start_time',
        'end_time',
        'status',
        'allow',
        'campaign_type',
        'batch_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'allow' => 'boolean',
    ];

    /**
     * Get the tenant that owns the campaign.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the provider for the campaign.
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the agent assigned to the campaign.
     */
    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * Get the contacts for the campaign.
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }

    /**
     * Scope a query to only include active campaigns.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('allow', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }

    /**
     * Get the total number of contacts in the campaign.
     *
     * @return int
     */
    public function getContactCountAttribute()
    {
        return $this->contacts()->count();
    }

    /**
     * Get the count of contacts by status.
     *
     * @return array
     */
    public function getContactStatsByStatusAttribute()
    {
        return $this->contacts()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

       /**
     * Boot the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($campaign) {
            // Skip if slug is already set
            if (!empty($campaign->slug)) {
                return;
            }

            // Generate a unique slug
            $baseSlug = Str::slug($campaign->name);

            // Handle empty slugs (non-latin characters)
            if (empty($baseSlug)) {
                $baseSlug = 'campaign-' . substr(md5($campaign->name), 0, 8);
            }

            // Add unique identifiers
            $timestamp = now()->format('YmdHis');
            $microseconds = sprintf('%06d', now()->microsecond);
            $uniqueId = Str::uuid()->toString();
            $tenantPrefix = $campaign->tenant_id ? substr(md5($campaign->tenant_id), 0, 6) : 'notenant';

            // Combine for uniqueness
            $uniqueSlug = $tenantPrefix . '_' .
                          $baseSlug . '_' .
                          $timestamp .
                          $microseconds . '_' .
                          substr($uniqueId, 0, 8);

            // Check length and truncate if needed
            if (strlen($uniqueSlug) > 190) {
                $uniqueSlug = $tenantPrefix . '_' .
                              substr(md5($baseSlug), 0, 8) . '_' .
                              $timestamp .
                              $microseconds . '_' .
                              substr($uniqueId, 0, 8);
            }

            // Set the slug
            $campaign->slug = $uniqueSlug;

            // Double-check for uniqueness (extra safety)
            $exists = static::where('slug', $uniqueSlug)->exists();
            if ($exists) {
                // If collision happened (extremely unlikely), add more randomness
                $campaign->slug = $uniqueSlug . '_' . Str::random(8);
            }
        });
    }

    public function updateCampaignStatus()
    {
        // Get contact status counts
        $statusCounts = $this->contacts()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Total contacts
        $totalContacts = $this->contacts()->count();

        // Determine campaign status
        $newStatus = $this->determineCampaignStatus($statusCounts, $totalContacts);

        // Update status if changed
        if ($newStatus !== $this->status) {
            $this->update(['status' => $newStatus]);
        }

        return $this;
    }

    /**
     * Determine campaign status based on contact statuses.
     *
     * @param array $statusCounts
     * @param int $totalContacts
     * @return string
     */
    protected function determineCampaignStatus(array $statusCounts, int $totalContacts): string
    {
        // Campaign not started
        if ($totalContacts === 0) {
            return 'not_start';
        }

        // All contacts processed or unreachable
        if (
            (isset($statusCounts['Talking']) || isset($statusCounts['Routing'])) &&
            ($statusCounts['Talking'] + $statusCounts['Routing'] ?? 0) === $totalContacts
        ) {
            return 'processed';
        }

        // Ongoing campaign with active calling
        if (isset($statusCounts['calling']) && $statusCounts['calling'] > 0) {
            return 'calling';
        }

        // Some contacts still new
        if (isset($statusCounts['new']) && $statusCounts['new'] > 0) {
            return 'not_complete';
        }

        // Fallback to current status
        return $this->status;
    }

    /**
     * Automatically update campaign status after a contact's status changes.
     *
     * @param Contact $contact
     * @return void
     */
    public function handleContactStatusChange(Contact $contact)
    {
        $this->updateCampaignStatus();
    }

    /**
     * Scope a query to campaigns that need status updates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedingStatusUpdate($query)
    {
        return $query->whereIn('status', ['not_start', 'calling', 'not_complete']);
    }

    /**
     * Batch update statuses for all active campaigns.
     *
     * @return void
     */
    // public static function updateAllCampaignStatuses()
    // {
    //     self::active()->needingStatusUpdate()->get()->each(function ($campaign) {
    //         $campaign->updateCampaignStatus();
    //     });
    // }
}
