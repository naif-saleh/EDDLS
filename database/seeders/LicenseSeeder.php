<?php
// database/seeders/LicenseSeeder.php
namespace Database\Seeders;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LicenseSeeder extends Seeder
{
    public function run()
    {
        // License for Example Company
        // License::create([
        //     'tenant_id' => 1,
        //     'license_key' => Str::random(16),
        //     'valid_from' => Carbon::now(),
        //     'valid_until' => Carbon::now()->addYear(),
        //     'max_campaigns' => 10,
        //     'max_agents' => 20,
        //     'max_providers' => 5,
        //     'max_dist_calls' => 50,
        //     'max_contacts_per_campaign' => 2000,
        //     'is_active' => true,
        // ]);

        // // License for Test Organization
        // License::create([
        //     'tenant_id' => 2,
        //     'license_key' => Str::random(16),
        //     'valid_from' => Carbon::now(),
        //     'valid_until' => Carbon::now()->addMonths(6),
        //     'max_campaigns' => 5,
        //     'max_agents' => 10,
        //     'max_providers' => 3,
        //     'max_concurrent_calls' => 20,
        //     'max_contacts_per_campaign' => 1000,
        //     'is_active' => true,
        // ]);
    }
}
