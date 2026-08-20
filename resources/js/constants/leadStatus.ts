import type { LeadStatus } from '@/types';

export const LEAD_STATUS_ORDER: LeadStatus[] = [
    'new',
    'contacted',
    'conversation',
    'meeting',
    'proposal',
    'won',
    'lost',
];

export const LEAD_STATUS_LABELS: Record<LeadStatus, string> = {
    new: 'Novo',
    contacted: 'Contatado',
    conversation: 'Em conversa',
    meeting: 'Reunião',
    proposal: 'Proposta',
    won: 'Ganho',
    lost: 'Perdido',
};

export const LEAD_STATUS_BADGE_CLASSES: Record<LeadStatus, string> = {
    new: 'bg-status-new-bg text-status-new-fg',
    contacted: 'bg-status-contacted-bg text-status-contacted-fg',
    conversation: 'bg-status-conversation-bg text-status-conversation-fg',
    meeting: 'bg-status-meeting-bg text-status-meeting-fg',
    proposal: 'bg-status-proposal-bg text-status-proposal-fg',
    won: 'bg-status-won-bg text-status-won-fg',
    lost: 'bg-status-lost-bg text-status-lost-fg',
};
