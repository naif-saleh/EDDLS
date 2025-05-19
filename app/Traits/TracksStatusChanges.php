<?php

namespace App\Traits;

use App\Services\SystemLogService;

trait TracksStatusChanges
{
    /**
     * Boot the trait
     */
    protected static function bootTracksStatusChanges()
    {
        static::updating(function ($model) {
            // Check if status is being changed
            if ($model->isDirty('status')) {
                $originalStatus = $model->getOriginal('status');
                $newStatus = $model->status;

                app(SystemLogService::class)->log(
                    logType: 'status_change',
                    action: strtolower(class_basename($model)) . '_status_changed',
                    model: $model,
                    description: class_basename($model) . " status changed from '{$originalStatus}' to '{$newStatus}'",
                    previousData: ['status' => $originalStatus],
                    newData: ['status' => $newStatus]
                );
            }
        });
    }
} 