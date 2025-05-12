<?php


// database/seeders/CallLogSeeder.php
namespace Database\Seeders;

use App\Models\CallLog;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CallLogSeeder extends Seeder
{
    public function run()
    {
        // // Create sample call logs for some contacts
        // $contacts = Contact::take(10)->get();

        // foreach ($contacts as $contact) {
        //     $campaign = $contact->campaign;

        //     // Update contact status
        //     $contact->status = 'answer';
        //     $contact->start_calling = Carbon::now()->subMinutes(rand(5, 30));
        //     $contact->end_calling = Carbon::now();
        //     $contact->attempt_count = 1;
        //     $contact->save();

        //     // Create call log
        //     CallLog::create([
        //         'contact_id' => $contact->id,
        //         'campaign_id' => $campaign->id,
        //         'provider_id' => $campaign->provider_id,
        //         'agent_id' => $campaign->agent_id,
        //         'call_duration' => rand(30, 300), // 30 seconds to 5 minutes
        //         'call_status' => 'completed',
        //         'recording_url' => 'recordings/call_' . $contact->id . '.mp3',
        //         'notes' => 'Sample call log for demo',
        //     ]);
        // }
    }
}
