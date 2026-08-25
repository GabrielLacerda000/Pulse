import type { CampaignStatus } from '@/types';

export const CAMPAIGN_STATUS_ORDER: CampaignStatus[] = [
    'draft',
    'running',
    'completed',
    'failed',
];

export const CAMPAIGN_STATUS_LABELS: Record<CampaignStatus, string> = {
    draft: 'Rascunho',
    running: 'Em andamento',
    completed: 'Concluída',
    failed: 'Falhou',
};
