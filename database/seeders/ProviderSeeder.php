<?php


// database/seeders/ProviderSeeder.php
namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderSeeder extends Seeder
{
    public function run()
    {
        // For Example Company
        Provider::create([
            'name' => 'Dialer Provider 1',
            'extension' => '101',
            'tenant_id' => 1,
            'status' => 'active',
            'provider_type' => 'dialer',
            'slug' => Str::slug('Dialer Provider 1'),
        ]);

        Provider::create([
            'name' => 'Distributor Provider 1',
            'extension' => '102',
            'tenant_id' => 1,
            'status' => 'active',
            'provider_type' => 'distributor',
            'slug' => Str::slug('Distributor Provider 1'),
        ]);

        // For Test Organization
        Provider::create([
            'name' => 'Dialer Provider 2',
            'extension' => '201',
            'tenant_id' => 2,
            'status' => 'active',
            'provider_type' => 'dialer',
            'slug' => Str::slug('Dialer Provider 2'),
        ]);

        Provider::create([
            'name' => 'Distributor Provider 2',
            'extension' => '202',
            'tenant_id' => 2,
            'status' => 'active',
            'provider_type' => 'distributor',
            'slug' => Str::slug('Distributor Provider 2'),
        ]);
    }
}
