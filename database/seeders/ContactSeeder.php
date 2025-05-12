<?php

// database/seeders/ContactSeeder.php
namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContactSeeder extends Seeder
{
    public function run()
    {
        // Contacts for Sales Campaign 1
    //     $this->createContactsForCampaign(1, 20);

    //     // Contacts for Support Campaign 1
    //     $this->createContactsForCampaign(2, 15);

    //     // Contacts for Marketing Campaign 1
    //     $this->createContactsForCampaign(3, 25);

    //     // Contacts for Customer Survey
    //     $this->createContactsForCampaign(4, 18);
    // }

    // private function createContactsForCampaign($campaignId, $count)
    // {
    //     for ($i = 1; $i <= $count; $i++) {
    //         $name = 'Contact ' . $i;
    //         Contact::create([
    //         'campaign_id' => $campaignId,
    //         'phone_number' => '+' . rand(10000000000, 99999999999),
    //         'status' => 'new',
    //         'attempt_count' => 0,
    //         'additional_data' => json_encode([
    //             'name' => $name,
    //             'email' => 'contact' . $i . '@example.com',
    //         ]),
    //         'slug' => Str::slug($name . '-' . $campaignId . '-' . $i),
    //         ]);
    //     }
    }
}
