<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Services\ThreeCXIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncronizeAgentsFromPbxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


     /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30; // Shorter timeout for faster processing
    /**
     * Create a new job instance.
     */

     protected $tenant_id;
     protected $three_cxintegration_service_value;


    public function __construct($tenant, $three_cxintegration_service_value)
    {
        $this->tenant_id = $tenant;
        $this->three_cxintegration_service_value = $three_cxintegration_service_value;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Use a lock to prevent multiple overlapping executions
        $lockKey = 'adist_update_user_status_running';

        if (Cache::has($lockKey)) {
            Log::info('ADistUpdateUserStatusJob: Another instance is already running');

            return;
        }

        // Lock for 30 seconds max
        Cache::put($lockKey, true, 30);

        try {
            $startTime = microtime(true);

            // Use cached data if it's recent enough (within 10 seconds)
            $cacheKey = 'three_cx_users_data';
            $users = Cache::remember($cacheKey, 10, function (){
                try {
                    $result = $this->three_cxintegration_service_value;
                    if (isset($result['value']) && is_array($result['value'])) {
                        return $result;
                    }

                    return null;
                } catch (\Exception $e) {
                    Log::error('Failed to fetch users: '.$e->getMessage());

                    return null;
                }
            });

            if ($users && isset($users['value']) && is_array($users['value'])) {
                // Use a persistent database connection to avoid reconnection overhead
                $connection = DB::connection();
                $pdo = $connection->getPdo();

                // Check if connection is still alive
                if (! $pdo || ! $this->isConnectionAlive($pdo)) {
                    $connection->reconnect();
                    $pdo = $connection->getPdo();
                }

                // Proceed without wrapping in a transaction
                foreach ($users['value'] as $user) {
                    // Log::info('Processing user:', [
                    //     'id' => $user['Id'],
                    //     'name' => $user['DisplayName'],
                    //     'email' => $user['EmailAddress'],
                    //     'profile' => $user['CurrentProfileName']
                    // ]);
                    Agent::updateOrCreate(
                        ['three_cx_user_id' => $user['Id']],
                        [
                            'three_cx_user_id' => $user['Id'],
                            'tenant_id' => $this->tenant_id,
                            'slug' => Str::random(10),
                            'CurrentProfileName' => $user['CurrentProfileName'],
                            'name' => $user['DisplayName'],
                            'email' => $user['EmailAddress'],
                            'QueueStatus' => $user['QueueStatus'],
                            'extension' => $user['Number'],
                            'ContactImage' => $user['FirstName'],
                        ]
                    );
                }

                $executionTime = round(microtime(true) - $startTime, 3);
                Log::info('ADistUpdateUserStatusJob: ✅ Updated '.count($users['value'])." users in {$executionTime}s");
            }
        } catch (\Exception $e) {
            Log::error('ADistUpdateUserStatusJob Error: '.$e->getMessage());
            throw $e;
        } finally {
            Cache::forget($lockKey);
        }
    }

    /**
     * Check if database connection is still alive
     */
    private function isConnectionAlive($pdo): bool
    {
        try {
            $pdo->query('SELECT 1');

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ADistUpdateUserStatusJob failed: '.$exception->getMessage());
        Cache::forget('adist_update_user_status_running');
    }
}
