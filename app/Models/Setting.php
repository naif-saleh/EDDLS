<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'tenant_id',
        'start_time',
        'end_time',
        'calls_at_time',
        'logo',
        'auto_call',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'calls_at_time' => 'integer',
        'auto_call' => 'boolean',
    ];

    /**
     * Get the tenant that owns the setting
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if the current time is within the operating hours
     *
     * @return bool
     */
    public function isWithinOperatingHours()
    {
        $now = now();
        $currentTime = $now->format('H:i');
        $currentDayOfWeek = $now->dayOfWeek;

        // If days_of_week is set and the current day is not in the array, return false
        if (! empty($this->days_of_week) && ! in_array($currentDayOfWeek, $this->days_of_week)) {
            return false;
        }

        return $currentTime >= $this->start_time->format('H:i') &&
               $currentTime <= $this->end_time->format('H:i');
    }
}
