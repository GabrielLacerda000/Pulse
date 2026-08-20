<?php

namespace App\Enums;

enum LeadStatus: string
{
    case NEW = 'new';
    case CONTACTED = 'contacted';
    case CONVERSATION = 'conversation';
    case MEETING = 'meeting';
    case PROPOSAL = 'proposal';
    case WON = 'won';
    case LOST = 'lost';
}
