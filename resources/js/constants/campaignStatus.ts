import type { BadgeVariants } from '@/components/ui/badge';
import type { CampaignStatus } from '@/types';

export const CAMPAIGN_STATUS_ORDER: CampaignStatus[] = [
    'draft',
    'running',
    'completed',
    'partial',
    'failed',
];

export const CAMPAIGN_STATUS_LABELS: Record<CampaignStatus, string> = {
    draft: 'Rascunho',
    running: 'Em andamento',
    completed: 'Concluída',
    partial: 'Sucesso parcial',
    failed: 'Falhou',
};

export const CAMPAIGN_STATUS_VARIANTS: Record<
    CampaignStatus,
    NonNullable<BadgeVariants['variant']>
> = {
    draft: 'secondary',
    running: 'outline',
    completed: 'default',
    partial: 'outline',
    failed: 'destructive',
};

export const CAMPAIGN_STATUS_CLASSES: Partial<Record<CampaignStatus, string>> = {
    completed:
        'border-transparent bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
    partial:
        'border-transparent bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
};
