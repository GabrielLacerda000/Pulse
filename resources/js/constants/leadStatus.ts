import type { LeadStatus } from '@/types';

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
    new: 'bg-[#fff8e0] text-[#746019]',
    contacted: 'bg-[#f5f3ff] text-[#4262ff]',
    conversation: 'bg-[#c3faf5] text-[#187574]',
    meeting: 'bg-[#f5f3ff] text-[#2a41b6]',
    proposal: 'bg-[#ffe6cd] text-[#1c1c1e]',
    won: 'bg-[#00b473] text-white',
    lost: 'bg-[#fbd4d4] text-[#600000]',
};
