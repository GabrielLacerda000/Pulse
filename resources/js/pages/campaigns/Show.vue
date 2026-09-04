<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CampaignProgressBar from '@/components/campaigns/CampaignProgressBar.vue';
import CampaignRecipientMessageDialog from '@/components/campaigns/CampaignRecipientMessageDialog.vue';
import CampaignRecipientsFilterBar from '@/components/campaigns/CampaignRecipientsFilterBar.vue';
import CampaignRecipientStatusBadge from '@/components/campaigns/CampaignRecipientStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import LeadsPagination from '@/components/LeadsPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCampaignRecipientFilters } from '@/composables/useCampaignRecipientFilters';
import { usePhoneMask } from '@/composables/usePhoneMask';
import {
    CAMPAIGN_STATUS_CLASSES,
    CAMPAIGN_STATUS_LABELS,
    CAMPAIGN_STATUS_VARIANTS,
} from '@/constants/campaignStatus';
import { PULSE_WPP_ERROR_CODE_LABELS } from '@/constants/pulseWppErrorCode';
import { dashboard } from '@/routes';
import { create, index, show } from '@/routes/campaigns';
import type {
    CampaignProgress,
    CampaignRecipient,
    CampaignRecipientFilters,
    FailedCampaignRecipient,
    Paginated,
} from '@/types';

const props = defineProps<{
    campaign: {
        id: number;
        name: string;
        status: CampaignProgress['status'];
        can_retry_failed: boolean;
        started_at: string | null;
        completed_at: string | null;
    };
    progress: CampaignProgress;
    recipients: Paginated<CampaignRecipient>;
    filters: CampaignRecipientFilters;
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
            { title: 'Progresso da campanha', href: '' },
        ],
    },
});

const currentStatus = ref(props.campaign.status);
const liveProgress = ref<CampaignProgress>(props.progress);

const { filters, goToPage } = useCampaignRecipientFilters(
    show.url({ campaign: props.campaign.id }),
    props.filters,
);

const { formatPhoneDisplay } = usePhoneMask();

const successRate = computed(() => {
    if (liveProgress.value.total === 0) {
        return 0;
    }

    return Math.round(
        (liveProgress.value.sent / liveProgress.value.total) * 100,
    );
});

const dateFormatter = new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

function formatDate(value: string | null): string | null {
    return value ? dateFormatter.format(new Date(value)) : null;
}

function formatDuration(
    startedAt: string | null,
    completedAt: string | null,
): string | null {
    if (!startedAt || !completedAt) {
        return null;
    }

    const totalMinutes = Math.round(
        (new Date(completedAt).getTime() - new Date(startedAt).getTime()) /
            60000,
    );
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours === 0) {
        return `${minutes}min`;
    }

    return `${hours}h ${minutes}min`;
}

const startedAtLabel = computed(() => formatDate(props.campaign.started_at));
const executionDuration = computed(() =>
    formatDuration(props.campaign.started_at, props.campaign.completed_at),
);

function handleProgressUpdate(data: CampaignProgress) {
    const wasRunning = currentStatus.value === 'running';
    liveProgress.value = data;
    currentStatus.value = data.status;

    if (data.status === 'running') {
        router.reload({ only: ['recipients'] });

        return;
    }

    if (wasRunning) {
        router.reload({ only: ['recipients', 'failedRecipients', 'campaign'] });
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
            <div class="flex flex-col items-end gap-1">
                <Badge
                    :variant="CAMPAIGN_STATUS_VARIANTS[currentStatus]"
                    :class="CAMPAIGN_STATUS_CLASSES[currentStatus]"
                >
                    {{ CAMPAIGN_STATUS_LABELS[currentStatus] }}
                </Badge>
                <p v-if="startedAtLabel" class="text-xs text-muted-foreground">
                    Executada em {{ startedAtLabel }}
                </p>
            </div>
        </div>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle>Resumo</CardTitle>
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
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div
                        class="rounded-xl border border-border p-4 text-center"
                    >
                        <p class="text-2xl font-semibold text-foreground">
                            {{ liveProgress.total }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Destinatários
                        </p>
                    </div>
                    <div
                        class="rounded-xl border border-border p-4 text-center"
                    >
                        <p class="text-2xl font-semibold text-foreground">
                            {{ liveProgress.sent }}
                        </p>
                        <p class="text-xs text-muted-foreground">Enviadas</p>
                    </div>
                    <div
                        class="rounded-xl border border-border p-4 text-center"
                    >
                        <p class="text-2xl font-semibold text-foreground">
                            {{ liveProgress.failed }}
                        </p>
                        <p class="text-xs text-muted-foreground">Falharam</p>
                    </div>
                    <div
                        class="rounded-xl border border-border p-4 text-center"
                    >
                        <p class="text-2xl font-semibold text-foreground">
                            {{ successRate }}%
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Taxa de sucesso
                        </p>
                    </div>
                </div>

                <p
                    v-if="executionDuration"
                    class="text-sm text-muted-foreground"
                >
                    Tempo de execução: {{ executionDuration }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Desempenho</CardTitle>
            </CardHeader>
            <CardContent>
                <CampaignProgressBar
                    :campaign-id="props.campaign.id"
                    :initial-progress="props.progress"
                    @update="handleProgressUpdate"
                />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Destinatários</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-4">
                <CampaignRecipientsFilterBar
                    v-model:search="filters.search"
                    v-model:status="filters.status"
                />

                <div class="overflow-x-auto rounded-xl border border-border">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr
                                class="border-b border-border bg-muted text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                <th class="px-4 py-3 font-semibold">Nome</th>
                                <th class="px-4 py-3 font-semibold">
                                    Telefone
                                </th>
                                <th class="px-4 py-3 font-semibold">
                                    Mensagem
                                </th>
                                <th class="px-4 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 font-semibold">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-if="recipients.data.length === 0"
                                class="border-b border-border/60"
                            >
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nenhum destinatário encontrado.
                                </td>
                            </tr>
                            <tr
                                v-for="recipient in recipients.data"
                                :key="recipient.id"
                                class="border-b border-border/60"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ recipient.company_name }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ formatPhoneDisplay(recipient.phone) }}
                                </td>
                                <td
                                    class="max-w-xs truncate px-4 py-3 text-muted-foreground"
                                >
                                    {{ recipient.rendered_content || '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <CampaignRecipientStatusBadge
                                            :status="recipient.status"
                                        />
                                        <span
                                            v-if="
                                                recipient.status === 'failed' &&
                                                recipient.error_code
                                            "
                                            class="text-xs text-destructive"
                                        >
                                            {{
                                                PULSE_WPP_ERROR_CODE_LABELS[
                                                    recipient.error_code
                                                ]
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <CampaignRecipientMessageDialog
                                        :recipient="recipient"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <LeadsPagination
                    :current-page="recipients.current_page"
                    :last-page="recipients.last_page"
                    @change="goToPage"
                />
            </CardContent>
        </Card>
    </div>
</template>
