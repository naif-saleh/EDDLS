<?php


// app/Models/Contact.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $fillable = [
        'slug',
        'campaign_id',
        'phone_number',
        'status',
        'start_calling',
        'end_calling',
        'attempt_count',
        'additional_data',
        'call_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */

    protected $casts = [
        'start_calling' => 'datetime',
        'end_calling' => 'datetime',
        'additional_data' => 'json',
        'status' => 'string',
    ];

     /**
     * Get the campaign that owns the contact.
     */
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function callLogs()
    {
        return $this->hasMany(CallLog::class);
    }


    /**
     * Scope a query to only include contacts with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

     /**
     * Scope a query to only include contacts that haven't been called yet.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotCalled($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope a query to only include contacts with less than the maximum attempts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $maxAttempts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnderMaxAttempts($query, $maxAttempts = 3)
    {
        return $query->where('attempt_count', '<', $maxAttempts);
    }


    /**
     * Mark this contact as being called.
     *
     * @return bool
     */
    public function markAsCalling()
    {
        return $this->update([
            'status' => 'called',
            'start_calling' => now(),
            'attempt_count' => $this->attempt_count + 1,
        ]);
    }

    /**
     * Mark this contact call as completed.
     *
     * @param  string  $status
     * @return bool
     */
    public function completeCall($status)
    {
        if (!in_array($status, ['answer', 'no_answer'])) {
            $status = 'called';
        }

        return $this->update([
            'status' => $status,
            'end_calling' => now(),
        ]);
    }
}
