<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Plus } from '@lucide/vue';
import CampaignDeleteDialog from '@/components/campaigns/CampaignDeleteDialog.vue';
import CampaignsFilterBar from '@/components/campaigns/CampaignsFilterBar.vue';
import Heading from '@/components/Heading.vue';
import LeadsPagination from '@/components/LeadsPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCampaignFilters } from '@/composables/useCampaignFilters';
import { CAMPAIGN_STATUS_LABELS } from '@/constants/campaignStatus';
import { dashboard } from '@/routes';
import { create, edit, index, show } from '@/routes/campaigns';
import type { Campaign, CampaignFilters, Paginated } from '@/types';

const props = defineProps<{
    campaigns: Paginated<Campaign>;
    filters: CampaignFilters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
        ],
    },
});

const { filters, goToPage } = useCampaignFilters(props.filters);
</script>

<template>
    <Head title="Campanhas" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Campanhas"
                description="Gerencie suas campanhas de disparo em massa pelo WhatsApp"
            />

            <Button as-child class="rounded-full">
                <Link :href="create()">
                    <Plus class="size-4" />
                    Nova campanha
                </Link>
            </Button>
        </div>

        <CampaignsFilterBar
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
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Leads</th>
                        <th class="px-4 py-3 font-semibold">Enviadas</th>
                        <th class="px-4 py-3 font-semibold">Falhas</th>
                        <th class="px-4 py-3 font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="props.campaigns.data.length === 0"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nenhuma campanha cadastrada.
                        </td>
                    </tr>
                    <tr
                        v-for="campaign in props.campaigns.data"
                        :key="campaign.id"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ campaign.name }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge variant="secondary">{{
                                CAMPAIGN_STATUS_LABELS[campaign.status]
                            }}</Badge>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ campaign.recipients_count }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ campaign.sent_count }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ campaign.failed_count }}
                        </td>
                        <td class="px-4 py-3">
                            <div
                                v-if="campaign.status === 'draft'"
                                class="flex items-center gap-1"
                            >
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Editar"
                                >
                                    <Link :href="edit(campaign.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <CampaignDeleteDialog :campaign="campaign" />
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-1"
                            >
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Ver progresso"
                                >
                                    <Link :href="show(campaign.id)">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
                                <CampaignDeleteDialog
                                    v-if="campaign.status !== 'running'"
                                    :campaign="campaign"
                                />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <LeadsPagination
            :current-page="props.campaigns.current_page"
            :last-page="props.campaigns.last_page"
            @change="goToPage"
        />
    </div>
</template>
