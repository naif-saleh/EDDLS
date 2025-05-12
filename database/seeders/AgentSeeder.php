<?php


// database/seeders/AgentSeeder.php
namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentSeeder extends Seeder
{
    public function run()
    {
        // For Example Company
        // Agent::create([
        //     'name' => 'Agent 1',
        //     'slug' => Str::slug('Agent 1-' . Str::random(6)),
        //     'extension' => '1001',
        //     'tenant_id' => 1,
        //     'provider_id' => 1,
        //     'status' => 'active',
        // ]);

        // Agent::create([
        //     'name' => 'Agent 2',
        //     'slug' => Str::slug('Agent 2-' . Str::random(6)),
        //     'extension' => '1002',
        //     'tenant_id' => 1,
        //     'provider_id' => 1,
        //     'status' => 'active',
        // ]);

        // // For Test Organization
        // Agent::create([
        //     'name' => 'Agent 3',
        //     'slug' => Str::slug('Agent 3-' . Str::random(6)),
        //     'extension' => '401',
        //     'tenant_id' => 2,
        //     'provider_id' => 2,
        //     'status' => 'active',
        // ]);

        // Agent::create([
        //     'name' => 'Agent 4',
        //     'slug' => Str::slug('Agent 4-' . Str::random(6)),
        //     'extension' => '402',
        //     'tenant_id' => 2,
        //     'provider_id' => 2,
        //     'status' => 'active',
        // ]);
    }
}
