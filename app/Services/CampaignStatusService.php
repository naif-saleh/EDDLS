<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class CampaignStatusService
{
    /**
     * Update status for a specific campaign
     *
     * @param Campaign $campaign
     * @return Campaign
     */
    public function updateSingleCampaignStatus(Campaign $campaign)
    {
        $oldStatus = $campaign->status;
        $campaign->updateCampaignStatus();

        if ($oldStatus !== $campaign->status) {
            Log::info("Campaign status updated", [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'old_status' => $oldStatus,
                'new_status' => $campaign->status
            ]);
        }

        return $campaign;
    }

    /**
     * Update status for all campaigns that need updates
     *
     * @return int Number of campaigns updated
     */
    public function updateAllCampaignStatuses()
    {
        $campaigns = Campaign::active()->needingStatusUpdate()->get();
        $updatedCount = 0;

        foreach ($campaigns as $campaign) {
            $oldStatus = $campaign->status;
            $campaign->updateCampaignStatus();

            if ($oldStatus !== $campaign->status) {
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Handle contact status change and update campaign accordingly
     *
     * @param Contact $contact
     * @return void
     */
    public function handleContactStatusChange(Contact $contact)
    {
        $campaign = $contact->campaign;
        if ($campaign) {
            $this->updateSingleCampaignStatus($campaign);
        }
    }

    /**
     * Handle call completion and update related campaign status
     *
     * @param int $campaignId
     * @param string $callStatus
     * @return void
     */
    public function handleCallCompletion(int $campaignId, string $callStatus = null)
    {
        $campaign = Campaign::find($campaignId);
        if ($campaign) {
            $this->updateSingleCampaignStatus($campaign);
        }
    }
}
