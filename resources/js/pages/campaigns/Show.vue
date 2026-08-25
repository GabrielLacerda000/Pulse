<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle } from '@lucide/vue';
import { computed, ref } from 'vue';
import CampaignProgressBar from '@/components/campaigns/CampaignProgressBar.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CAMPAIGN_STATUS_LABELS } from '@/constants/campaignStatus';
import { dashboard } from '@/routes';
import { create, index } from '@/routes/campaigns';
import type { CampaignProgress, FailedCampaignRecipient } from '@/types';

const props = defineProps<{
    campaign: {
        id: number;
        name: string;
        status: CampaignProgress['status'];
        can_retry_failed: boolean;
    };
    progress: CampaignProgress;
    failedRecipients: FailedCampaignRecipient[];
}>();

const retryLeadIds = computed(() =>
    props.failedRecipients
        .map((recipient) => recipient.lead_id)
        .filter((leadId): leadId is number => leadId !== null),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
            { title: props.campaign.name, href: '' },
        ],
    },
});

const currentStatus = ref(props.campaign.status);
const liveProgress = ref<CampaignProgress>(props.progress);

function handleProgressUpdate(data: CampaignProgress) {
    const wasRunning = currentStatus.value === 'running';
    liveProgress.value = data;
    currentStatus.value = data.status;

    if (wasRunning && data.status !== 'running') {
        router.reload({ only: ['failedRecipients'] });
    }
}
</script>

<template>
    <Head :title="props.campaign.name" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <Heading
                :title="props.campaign.name"
                description="Progresso do disparo da campanha"
            />
            <Badge :variant="currentStatus === 'failed' ? 'destructive' : 'secondary'">
                {{ CAMPAIGN_STATUS_LABELS[currentStatus] }}
            </Badge>
        </div>

        <Card v-if="currentStatus === 'running'" class="max-w-xl">
            <CardHeader>
                <CardTitle>Enviando mensagens...</CardTitle>
            </CardHeader>
            <CardContent>
                <CampaignProgressBar
                    :campaign-id="props.campaign.id"
                    :initial-progress="props.progress"
                    @update="handleProgressUpdate"
                />
            </CardContent>
        </Card>

        <Card v-else class="max-w-xl">
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Resumo do envio</CardTitle>
                <Button
                    v-if="props.campaign.can_retry_failed"
                    as-child
                    variant="outline"
                    size="sm"
                    class="rounded-full"
                >
                    <Link :href="create({ query: { lead_ids: retryLeadIds } })">
                        Tentar novamente com falhas
                    </Link>
                </Button>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <div class="flex gap-6 text-sm">
                    <p>
                        <span class="font-medium text-foreground">{{
                            liveProgress.sent
                        }}</span>
                        enviadas com sucesso
                    </p>
                    <p>
                        <span class="font-medium text-foreground">{{
                            liveProgress.failed
                        }}</span>
                        falharam
                    </p>
                </div>

                <div
                    v-if="props.failedRecipients.length > 0"
                    class="flex flex-col gap-2"
                >
                    <p class="text-sm font-medium text-foreground">Falhas</p>
                    <ul class="flex flex-col gap-1 text-sm text-muted-foreground">
                        <li
                            v-for="recipient in props.failedRecipients"
                            :key="recipient.id"
                            class="flex items-center justify-between gap-2 border-b border-border/60 py-1 last:border-b-0"
                        >
                            <span
                                >{{ recipient.company_name }} ({{
                                    recipient.phone
                                }})</span
                            >
                            <span class="flex items-center gap-1 text-destructive">
                                <AlertTriangle class="size-3.5" />
                                {{ recipient.error_code }}
                            </span>
                        </li>
                    </ul>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
