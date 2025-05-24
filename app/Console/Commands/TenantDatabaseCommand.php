<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Console\Command;

class TenantDatabaseCommand extends Command
{
    protected $signature = 'tenant:database {action : The action to perform (create|delete|migrate|reset|repair)} {tenant? : The tenant slug}';

    protected $description = 'Manage tenant databases';

    protected $tenantService;

    public function __construct(TenantService $tenantService)
    {
        parent::__construct();
        $this->tenantService = $tenantService;
    }

    public function handle()
    {
        $action = $this->argument('action');
        $tenantSlug = $this->argument('tenant');

        if ($tenantSlug) {
            $tenant = Tenant::where('slug', $tenantSlug)->first();
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantSlug}");
                return 1;
            }
            $this->processTenant($tenant, $action);
        } else {
            $tenants = Tenant::all();
            if ($tenants->isEmpty()) {
                $this->info('No tenants found.');
                return 0;
            }

            $this->info("Processing {$tenants->count()} tenant(s)...");
            foreach ($tenants as $tenant) {
                $this->processTenant($tenant, $action);
            }
        }

        return 0;
    }

    protected function processTenant(Tenant $tenant, string $action)
    {
        $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

        switch ($action) {
            case 'create':
                if ($tenant->database_created) {
                    $this->warn("Database already exists for {$tenant->name}");
                    return;
                }
                if ($this->tenantService->createDatabase($tenant)) {
                    $this->info("✓ Database created successfully for {$tenant->name}");
                } else {
                    $this->error("✗ Failed to create database for {$tenant->name}");
                }
                break;

            case 'delete':
                if ($this->confirm("Are you sure you want to delete the database for {$tenant->name}?")) {
                    if ($this->tenantService->deleteDatabase($tenant)) {
                        $this->info("✓ Database deleted successfully for {$tenant->name}");
                    } else {
                        $this->error("✗ Failed to delete database for {$tenant->name}");
                    }
                } else {
                    $this->info("Skipped deletion for {$tenant->name}");
                }
                break;

            case 'migrate':
                if (!$tenant->database_created) {
                    $this->error("No database exists for {$tenant->name}. Create it first.");
                    return;
                }
                if ($this->tenantService->runMigrations($tenant)) {
                    $this->info("✓ Migrations completed successfully for {$tenant->name}");
                } else {
                    $this->error("✗ Failed to run migrations for {$tenant->name}");
                }
                break;

            case 'reset':
                if (!$tenant->database_created) {
                    $this->error("No database exists for {$tenant->name}");
                    return;
                }
                if ($this->confirm("Are you sure you want to reset the database for {$tenant->name}? This will delete all data.")) {
                    if ($this->tenantService->resetDatabase($tenant)) {
                        $this->info("✓ Database reset successfully for {$tenant->name}");
                    } else {
                        $this->error("✗ Failed to reset database for {$tenant->name}");
                    }
                } else {
                    $this->info("Skipped reset for {$tenant->name}");
                }
                break;

            case 'repair':
                $this->info("Attempting to repair database for {$tenant->name}...");
                if ($this->tenantService->repairDatabase($tenant)) {
                    $this->info("✓ Database repaired successfully for {$tenant->name}");
                } else {
                    $this->error("✗ Failed to repair database for {$tenant->name}");
                }
                break;

            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: create, delete, migrate, reset, repair");
                break;
        }
    }
}