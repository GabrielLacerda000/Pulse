<?php

namespace App\Enums;

enum LeadImportRowStatus: string
{
    case NEW = 'new';
    case DUPLICATE = 'duplicate';
    case INVALID = 'invalid';
}
