<?php

// database/seeders/TenantSeeder.php
namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
 
class TenantSeeder extends Seeder
{
    public function run()
    {
        Tenant::create([
            'name' => 'Example Company',
            'email' => 'admin@example.com',
            'phone' => '+1234567890',
            'status' => 'active',
            'api_key' => Str::random(32),
            'slug' => Str::slug('Example Company'),
        ]);

        Tenant::create([
            'name' => 'Test Organization',
            'email' => 'admin@test.org',
            'phone' => '+9876543210',
            'status' => 'active',
            'api_key' => Str::random(32),
            'slug' => Str::slug('Test Organization'),
        ]);
    }
}
