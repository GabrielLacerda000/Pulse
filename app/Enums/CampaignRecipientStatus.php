<?php

namespace App\Enums;

enum CampaignRecipientStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';
}
