<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\TenantService;

class TenantDatabaseCommand extends Command
{
    protected $signature = 'tenant:database {action : The action to perform (create|delete|migrate)} {tenant? : The tenant slug}';
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
            foreach ($tenants as $tenant) {
                $this->processTenant($tenant, $action);
            }
        }

        return 0;
    }

    protected function processTenant(Tenant $tenant, string $action)
    {
        $this->info("Processing tenant: {$tenant->name}");

        switch ($action) {
            case 'create':
                if ($this->tenantService->createDatabase($tenant)) {
                    $this->info("Database created successfully for {$tenant->name}");
                } else {
                    $this->error("Failed to create database for {$tenant->name}");
                }
                break;

            case 'delete':
                if ($this->tenantService->deleteDatabase($tenant)) {
                    $this->info("Database deleted successfully for {$tenant->name}");
                } else {
                    $this->error("Failed to delete database for {$tenant->name}");
                }
                break;

            case 'migrate':
                if ($this->tenantService->runMigrations($tenant)) {
                    $this->info("Migrations completed successfully for {$tenant->name}");
                } else {
                    $this->error("Failed to run migrations for {$tenant->name}");
                }
                break;

            default:
                $this->error("Unknown action: {$action}");
                break;
        }
    }
} 