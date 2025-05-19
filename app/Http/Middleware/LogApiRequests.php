<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\SystemLogService;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    protected $systemLogService;

    public function __construct(SystemLogService $systemLogService)
    {
        $this->systemLogService = $systemLogService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the response
        $response = $next($request);

        // Don't log requests for static files
        if (!$this->shouldLogRequest($request)) {
            return $response;
        }

        // Get request data, excluding sensitive information
        $requestData = $this->sanitizeData($request->all());

        // Get response data if it's JSON
        $responseData = null;
        if ($response instanceof Response && $response->headers->get('content-type') === 'application/json') {
            $responseData = $this->sanitizeData(json_decode($response->getContent(), true) ?? []);
        }

        // Log the API request
        $this->systemLogService->logApiRequest(
            endpoint: $request->path(),
            method: $request->method(),
            requestData: $requestData,
            responseData: $responseData,
            statusCode: $response->getStatusCode()
        );

        return $response;
    }

    /**
     * Determine if the request should be logged
     */
    protected function shouldLogRequest(Request $request): bool
    {
        // Skip logging for static files and common assets
        $skipExtensions = ['js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg'];
        $path = $request->path();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return !in_array($extension, $skipExtensions);
    }

    /**
     * Remove sensitive data from the array
     */
    protected function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'credit_card',
            'card_number',
            'cvv',
            'secret',
            'token',
            'api_key'
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }

        return $data;
    }
} 