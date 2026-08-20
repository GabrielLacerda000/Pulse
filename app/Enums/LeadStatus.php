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

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Novo',
            self::CONTACTED => 'Contatado',
            self::CONVERSATION => 'Em conversa',
            self::MEETING => 'Reunião',
            self::PROPOSAL => 'Proposta',
            self::WON => 'Ganho',
            self::LOST => 'Perdido',
        };
    }
}
