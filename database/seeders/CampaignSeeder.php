<?php

// database/seeders/CampaignSeeder.php
namespace Database\Seeders;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CampaignSeeder extends Seeder
{
    public function run()
    {
        // Dialer campaigns for Example Company
        // Campaign::create([
        //     'name' => 'Sales Campaign 1',
        //     'slug' => Str::slug('Sales Campaign 1'),
        //     'tenant_id' => 1,
        //     'provider_id' => 1, // Dialer Provider
        //     'agent_id' => null, // No agent for dialer
        //     'start_time' => Carbon::now()->addHour(),
        //     'end_time' => Carbon::now()->addDays(7),
        //     'status' => 'not_start',
        //     'allow' => true,
        //     'campaign_type' => 'dialer',
        // ]);

        // // Distributor campaign for Example Company
        // Campaign::create([
        //     'name' => 'Support Campaign 1',
        //     'slug' => Str::slug('Support Campaign 1'),
        //     'tenant_id' => 1,
        //     'provider_id' => 2, // Distributor Provider
        //     'agent_id' => 1, // Assign to Agent 1
        //     'start_time' => Carbon::now()->addHour(),
        //     'end_time' => Carbon::now()->addDays(5),
        //     'status' => 'not_start',
        //     'allow' => true,
        //     'campaign_type' => 'distributor',
        // ]);

        // // Dialer campaign for Test Organization
        // Campaign::create([
        //     'name' => 'Marketing Campaign 1',
        //     'slug' => Str::slug('Marketing Campaign 1'),
        //     'tenant_id' => 2,
        //     'provider_id' => 3, // Dialer Provider for Test Org
        //     'agent_id' => null,
        //     'start_time' => Carbon::now()->addHours(2),
        //     'end_time' => Carbon::now()->addDays(10),
        //     'status' => 'not_start',
        //     'allow' => true,
        //     'campaign_type' => 'dialer',
        // ]);

        // // Distributor campaign for Test Organization
        // Campaign::create([
        //     'name' => 'Customer Survey',
        //     'slug' => Str::slug('Customer Survey'),
        //     'tenant_id' => 2,
        //     'provider_id' => 4, // Distributor Provider for Test Org
        //     'agent_id' => 3, // Assign to Agent 3
        //     'start_time' => Carbon::now()->addDay(),
        //     'end_time' => Carbon::now()->addDays(14),
        //     'status' => 'not_start',
        //     'allow' => true,
        //     'campaign_type' => 'distributor',
        // ]);
    }
}
