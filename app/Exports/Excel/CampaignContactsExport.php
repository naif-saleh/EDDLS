<?php

namespace App\Exports\Excel;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CampaignContactsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $campaignId;

    public function __construct($campaignId)
    {
        $this->campaignId = $campaignId;
    }

    public function collection()
    {
        return Contact::where('campaign_id', $this->campaignId)
            ->select('phone_number', 'status', 'start_calling', 'end_calling', 'created_at')  
            ->get();
    }

    public function headings(): array
    {
        return ['Number', 'Status', 'Start Call', 'End Call', 'Uploaded'];
    }


    public function map($row): array
    {
        return [
            $row->phone_number,
            $row->status,
            $row->start_calling ?? 'not call',
            $row->end_calling ?? 'not call',
            $row->created_at,

        ];
    }
}