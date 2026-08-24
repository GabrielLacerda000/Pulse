<?php

namespace App\Enums;

enum CampaignRecipientStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
}
