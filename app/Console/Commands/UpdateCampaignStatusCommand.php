<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateCampaignStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of all active campaigns based on their contacts';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Starting campaign status update process...');

        // Get campaigns that need status updates
        $campaigns = Campaign::active()->needingStatusUpdate()->get();

        Log::info("Found {$campaigns->count()} campaigns that need status updates.");

        $updatedCount = 0;

        foreach ($campaigns as $campaign) {
            $oldStatus = $campaign->status;
            $campaign->updateCampaignStatus();

            if ($oldStatus !== $campaign->status) {
                $updatedCount++;
                Log::info("Campaign '{$campaign->name}' status changed from '{$oldStatus}' to '{$campaign->status}'");

                Log::info("Campaign status updated", [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'old_status' => $oldStatus,
                    'new_status' => $campaign->status
                ]);
            }
        }

        Log::info("Completed campaign status updates. {$updatedCount} campaigns were updated.");


    }
}
