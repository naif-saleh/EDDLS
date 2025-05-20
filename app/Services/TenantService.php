<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use PDO;
use PDOException;

class TenantService
{
    /**
     * Create a new database for the tenant
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function createDatabase(Tenant $tenant)
    {
        if ($tenant->database_created) {
            return true;
        }

        try {
            // Generate database credentials
            $databaseName = 'tenant_' . Str::slug($tenant->name) . '_' . Str::random(8);
            $databaseUsername = env('DB_USERNAME', 'devops');
            $databasePassword = env('DB_PASSWORD', '');

            // Create direct PDO connection to MySQL server as root
            $devopsPdo = new PDO(
                "mysql:host=" . env('DB_HOST', '127.0.0.1') . ";port=" . env('DB_PORT', '3306'),
                $databaseUsername,
                $databasePassword
            );

            // Set PDO error mode to exception
            $devopsPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database
            $devopsPdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
            
            // Grant privileges to user
            $devopsPdo->exec("GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$databaseUsername}'@'localhost'");
            $devopsPdo->exec("GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$databaseUsername}'@'%'");
            $devopsPdo->exec("FLUSH PRIVILEGES");

            // Update tenant with database credentials
            $tenant->update([
                'database_name' => $databaseName,
                'database_username' => $databaseUsername,
                'database_password' => $databasePassword,
                'database_created' => true,
            ]);

            // Run migrations for the new database
            $this->runMigrations($tenant);

            return true;
        } catch (PDOException $e) {
            \Log::error('Failed to create tenant database: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run migrations for a tenant's database
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function runMigrations(Tenant $tenant)
    {
        try {
            // Set the database connection for the tenant
            config([
                'database.connections.tenant' => [
                    'driver' => 'mysql',
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => $tenant->database_name,
                    'username' => $tenant->database_username,
                    'password' => $tenant->database_password,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ]
            ]);

            // Run migrations
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force' => true,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to run migrations for tenant: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a tenant's database
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function deleteDatabase(Tenant $tenant)
    {
        if (!$tenant->database_created) {
            return true;
        }

        try {
            // Create direct PDO connection to MySQL server as devops
            $devopsPdo = new PDO(
                "mysql:host=" . env('DB_HOST', '127.0.0.1') . ";port=" . env('DB_PORT', '3306'),
                'devops',
                env('DB_PASSWORD', '')
            );

            // Set PDO error mode to exception
            $devopsPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Drop database
            $devopsPdo->exec("DROP DATABASE IF EXISTS `{$tenant->database_name}`");
            $devopsPdo->exec("FLUSH PRIVILEGES");

            // Update tenant
            $tenant->update([
                'database_name' => null,
                'database_username' => null,
                'database_password' => null,
                'database_created' => false,
            ]);

            return true;
        } catch (PDOException $e) {
            \Log::error('Failed to delete tenant database: ' . $e->getMessage());
            return false;
        }
    }
} 