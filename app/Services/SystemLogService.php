<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SystemLogService
{
    /**
     * Log a create action.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $description
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function logCreate(Model $model, ?string $description = null, ?array $metadata = null): SystemLog
    {
        return $this->log(
            logType: 'create',
            action: 'created',
            model: $model,
            description: $description ?? "Created new " . class_basename($model),
            previousData: null,
            newData: $model->toArray(),
            metadata: $metadata
        );
    }

    /**
     * Log an update action.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $originalAttributes
     * @param  string|null  $description
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function logUpdate(Model $model, array $originalAttributes, ?string $description = null, ?array $metadata = null): SystemLog
    {
        return $this->log(
            logType: 'update',
            action: 'updated',
            model: $model,
            description: $description ?? "Updated " . class_basename($model),
            previousData: $originalAttributes,
            newData: $model->toArray(),
            metadata: $metadata
        );
    }

    /**
     * Log a delete action.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $description
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function logDelete(Model $model, ?string $description = null, ?array $metadata = null): SystemLog
    {
        return $this->log(
            logType: 'delete',
            action: 'deleted',
            model: $model,
            description: $description ?? "Deleted " . class_basename($model),
            previousData: $model->toArray(),
            newData: null,
            metadata: $metadata
        );
    }

    /**
     * Log a custom action.
     *
     * @param  string  $logType
     * @param  string  $action
     * @param  \Illuminate\Database\Eloquent\Model|null  $model
     * @param  string|null  $description
     * @param  array|null  $previousData
     * @param  array|null  $newData
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function log(
        string $logType,
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $previousData = null,
        ?array $newData = null,
        ?array $metadata = null
    ): SystemLog {
        $request = request();
        $user = Auth::user();

        // Determine tenant_id from either:
        // 1. The model's tenant_id if it exists
        // 2. The authenticated user's tenant_id
        // 3. The current tenant from the application container
        $tenantId = null;
        if ($model && method_exists($model, 'tenant') && $model->tenant) {
            $tenantId = $model->tenant->id;
        } elseif ($user && $user->tenant_id) {
            $tenantId = $user->tenant_id;
        } elseif (app()->has('current_tenant_id')) {
            $tenantId = app()->get('current_tenant_id');
        }

        $data = [
            'log_type' => $logType,
            'action' => $action,
            'description' => $description,
            'previous_data' => $previousData,
            'new_data' => $newData,
            'metadata' => $metadata,
            'user_id' => $user?->id,
            'tenant_id' => $tenantId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ];

        if ($model) {
            $data['model_type'] = get_class($model);
            $data['model_id'] = $model->getKey();
        }

        // TenantService::setConnection(Auth::user()->tenant);
        return SystemLog::create($data);
    }

    /**
     * Log a login action.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function logLogin(Model $user, ?array $metadata = null): SystemLog
    {
        return $this->log(
            logType: 'auth',
            action: 'login',
            model: $user,
            description: "User logged in",
            previousData: null,
            newData: null,
            metadata: $metadata
        );
    }

    /**
     * Log a logout action.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $user
     * @param  array|null  $metadata
     * @return \App\Models\SystemLog
     */
    public function logLogout(Model $user, ?array $metadata = null): SystemLog
    {
        return $this->log(
            logType: 'auth',
            action: 'logout',
            model: $user,
            description: "User logged out",
            previousData: null,
            newData: null,
            metadata: $metadata
        );
    }

    /**
     * Log API requests.
     *
     * @param  string  $endpoint
     * @param  string  $method
     * @param  array|null  $requestData
     * @param  array|null  $responseData
     * @param  int|null  $statusCode
     * @return \App\Models\SystemLog
     */
    public function logApiRequest(
        string $endpoint,
        string $method,
        ?array $requestData = null,
        ?array $responseData = null,
        ?int $statusCode = null
    ): SystemLog {
        return $this->log(
            logType: 'api',
            action: 'api_request',
            description: "$method request to $endpoint",
            previousData: $requestData,
            newData: $responseData,
            metadata: [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode
            ]
        );
    }
}
