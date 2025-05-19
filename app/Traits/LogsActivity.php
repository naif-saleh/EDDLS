<?php

namespace App\Traits;

use App\Services\SystemLogService;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            app(SystemLogService::class)->logCreate($model);
        });

        static::updated(function ($model) {
            $originalData = array_intersect_key(
                $model->getOriginal(),
                $model->getDirty()
            );

            app(SystemLogService::class)->logUpdate($model, $originalData);
        });

        static::deleted(function ($model) {
            app(SystemLogService::class)->logDelete($model);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                app(SystemLogService::class)->log(
                    logType: 'restore',
                    action: 'restored',
                    model: $model,
                    description: "Restored " . class_basename($model)
                );
            });
        }

        if (method_exists(static::class, 'forceDeleted')) {
            static::forceDeleted(function ($model) {
                app(SystemLogService::class)->log(
                    logType: 'delete',
                    action: 'force_deleted',
                    model: $model,
                    description: "Force deleted " . class_basename($model)
                );
            });
        }
    }
} 