<?php

// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            // TenantSeeder::class,
            // LicenseSeeder::class,
            // ProviderSeeder::class,
            // AgentSeeder::class,
            UserSeeder::class,
            // CampaignSeeder::class,
            // ContactSeeder::class,
            // CallLogSeeder::class,
        ]);
    }
}
