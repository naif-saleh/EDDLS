<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;
use PDOException;

class TenantService
{
    public static function setConnection($tenant)
    {
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
            ],
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Create a new database for the tenant
     */
    public function createDatabase(Tenant $tenant)
    {
        if ($tenant->database_created) {
            Log::info("Database already exists for tenant: {$tenant->name}");
            return true;
        }

        try {
            // Generate consistent database name
            $databaseName = $this->generateDatabaseName($tenant);
            $databaseUsername = env('DB_USERNAME', 'root');
            $databasePassword = env('DB_PASSWORD', '');

            // Create PDO connection to MySQL server
            $pdo = new PDO(
                'mysql:host='.env('DB_HOST', '127.0.0.1').';port='.env('DB_PORT', '3306'),
                $databaseUsername,
                $databasePassword
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check if database already exists
            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$databaseName]);
            
            if ($stmt->fetch()) {
                Log::info("Database {$databaseName} already exists, updating tenant record");
            } else {
                // Create database
                $pdo->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                Log::info("Database {$databaseName} created successfully");
            }

            // Update tenant with database credentials
            $tenant->update([
                'database_name' => $databaseName,
                'database_username' => $databaseUsername,
                'database_password' => $databasePassword,
                'database_created' => true,
            ]);

            // Run migrations immediately after database creation
            if (!$this->runMigrations($tenant)) {
                Log::error("Failed to run migrations after database creation for tenant: {$tenant->name}");
                return false;
            }

            return true;
        } catch (PDOException $e) {
            Log::error('Failed to create tenant database: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Generate consistent database name
     */
    private function generateDatabaseName(Tenant $tenant): string
    {
        // Use tenant ID for consistency instead of name slug
        return "tenant_{$tenant->id}_database";
    }

    /**
     * Verify database exists before attempting operations
     */
    private function verifyDatabaseExists(Tenant $tenant): bool
    {
        try {
            $databaseUsername = env('DB_USERNAME', 'root');
            $databasePassword = env('DB_PASSWORD', '');

            $pdo = new PDO(
                'mysql:host='.env('DB_HOST', '127.0.0.1').';port='.env('DB_PORT', '3306'),
                $databaseUsername,
                $databasePassword
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$tenant->database_name]);
            
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            Log::error('Failed to verify database existence: '.$e->getMessage());
            return false;
        }
    }

    /**
     * Run migrations for a tenant's database
     */
    public function runMigrations(Tenant $tenant)
    {
        if (!$tenant->database_created || !$tenant->database_name) {
            Log::error("No database configured for tenant: {$tenant->name}");
            return false;
        }

        // Verify database actually exists
        if (!$this->verifyDatabaseExists($tenant)) {
            Log::error("Database {$tenant->database_name} does not exist for tenant: {$tenant->name}");
            return false;
        }

        try {
            // Set the database connection for the tenant
            $this->configureTenantConnection($tenant);

            // Test the connection before proceeding
            try {
                DB::connection('tenant')->getPdo();
                Log::info("Successfully connected to tenant database: {$tenant->database_name}");
            } catch (\Exception $e) {
                Log::error("Failed to connect to tenant database: {$e->getMessage()}");
                return false;
            }

            // Disable foreign key checks
            DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');

            try {
                // Check if migrations have been run before
                $migrationTableExists = DB::connection('tenant')
                    ->getSchemaBuilder()
                    ->hasTable('migrations');

                if ($migrationTableExists) {
                    Log::info("Migrations table exists, running fresh migration for tenant: {$tenant->name}");
                    // Fresh migrate to avoid conflicts
                    Artisan::call('migrate:fresh', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                } else {
                    Log::info("Running initial migrations for tenant: {$tenant->name}");
                    // Run migrations normally
                    Artisan::call('migrate', [
                        '--database' => 'tenant',
                        '--path' => 'database/migrations/tenant',
                        '--force' => true,
                    ]);
                }

                Log::info("Migrations completed successfully for tenant: {$tenant->name}");

            } finally {
                // Re-enable foreign key checks
                DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');
            }

            // Seed tenant data after successful migration
            $this->seedTenantData($tenant);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to run migrations for tenant {$tenant->name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Configure tenant database connection
     */
    private function configureTenantConnection(Tenant $tenant)
    {
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
            ],
        ]);

        // Clear any existing connections
        DB::purge('tenant');
    }

    /**
     * Seed initial tenant data
     */
    private function seedTenantData(Tenant $tenant)
    {
        try {
            // Check if tenants table exists and insert tenant record
            if (DB::connection('tenant')->getSchemaBuilder()->hasTable('tenants')) {
                $existingTenant = DB::connection('tenant')
                    ->table('tenants')
                    ->where('id', $tenant->id)
                    ->first();

                if (!$existingTenant) {
                    DB::connection('tenant')->table('tenants')->insert([
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'email' => $tenant->email,
                        'database_name' => $tenant->database_name,
                        'database_username' => $tenant->database_username,
                        'database_password' => $tenant->database_password,
                        'database_created' => $tenant->database_created,
                        'created_at' => $tenant->created_at,
                        'updated_at' => $tenant->updated_at,
                    ]);
                    Log::info("Seeded tenant record for: {$tenant->name}");
                }
            }

            // Check if users table exists and insert tenant user
            if (DB::connection('tenant')->getSchemaBuilder()->hasTable('users')) {
                $existingUser = DB::connection('tenant')
                    ->table('users')
                    ->where('email', $tenant->email)
                    ->first();

                if (!$existingUser) {
                    DB::connection('tenant')->table('users')->insert([
                        'name' => $tenant->name,
                        'email' => $tenant->email,
                        'email_verified_at' => null,
                        'password' => bcrypt('default_password'),
                        'tenant_id' => $tenant->id,
                        'role' => 'tenant_admin',
                        'status' => 1,
                        'created_at' => $tenant->created_at,
                        'updated_at' => $tenant->updated_at,
                    ]);
                    Log::info("Seeded admin user for tenant: {$tenant->name}");
                }
            }
        } catch (\Exception $e) {
            Log::warning("Failed to seed tenant data for {$tenant->name}: {$e->getMessage()}");
        }
    }

    /**
     * Reset a tenant's database (clear all tables and re-migrate)
     */
    public function resetDatabase(Tenant $tenant)
    {
        if (!$tenant->database_created) {
            Log::info("No database exists for tenant: {$tenant->name}");
            return true;
        }

        if (!$this->verifyDatabaseExists($tenant)) {
            Log::error("Database {$tenant->database_name} does not exist for tenant: {$tenant->name}");
            return false;
        }

        try {
            $this->configureTenantConnection($tenant);

            // Reset all migrations
            Artisan::call('migrate:reset', [
                '--database' => 'tenant',
                '--force' => true,
            ]);

            Log::info("Database reset successfully for tenant: {$tenant->name}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to reset tenant database for {$tenant->name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Delete a tenant's database
     */
    public function deleteDatabase(Tenant $tenant)
    {
        if (!$tenant->database_created) {
            return true;
        }

        try {
            $databaseUsername = env('DB_USERNAME', 'root');
            $databasePassword = env('DB_PASSWORD', '');

            $rootPdo = new PDO(
                'mysql:host='.env('DB_HOST', '127.0.0.1').';port='.env('DB_PORT', '3306'),
                $databaseUsername,
                $databasePassword
            );

            $rootPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Drop database
            $rootPdo->exec("DROP DATABASE IF EXISTS `{$tenant->database_name}`");
            $rootPdo->exec('FLUSH PRIVILEGES');

            // Update tenant
            $tenant->update([
                'database_name' => null,
                'database_username' => null,
                'database_password' => null,
                'database_created' => false,
            ]);

            Log::info("Database deleted successfully for tenant: {$tenant->name}");
            return true;
        } catch (PDOException $e) {
            Log::error("Failed to delete tenant database for {$tenant->name}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Repair tenant database - recreate if missing
     */
    public function repairDatabase(Tenant $tenant)
    {
        Log::info("Attempting to repair database for tenant: {$tenant->name}");
        
        // Reset the database_created flag and try to create again
        $tenant->update(['database_created' => false]);
        
        return $this->createDatabase($tenant);
    }
}