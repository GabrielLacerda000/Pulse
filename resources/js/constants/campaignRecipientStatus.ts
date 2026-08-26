import type { CampaignRecipientStatus } from '@/types';

export const CAMPAIGN_RECIPIENT_STATUS_LABELS: Record<CampaignRecipientStatus, string> = {
    pending: 'Pendente',
    processing: 'Enviando',
    sent: 'Enviada',
    failed: 'Falhou',
};

export const CAMPAIGN_RECIPIENT_STATUS_BADGE_CLASSES: Record<CampaignRecipientStatus, string> = {
    pending: 'bg-status-new-bg text-status-new-fg',
    processing: 'bg-status-contacted-bg text-status-contacted-fg',
    sent: 'bg-status-won-bg text-status-won-fg',
    failed: 'bg-status-lost-bg text-status-lost-fg',
};
