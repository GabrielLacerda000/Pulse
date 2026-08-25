<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus } from '@lucide/vue';
import CampaignDeleteDialog from '@/components/campaigns/CampaignDeleteDialog.vue';
import Heading from '@/components/Heading.vue';
import LeadsPagination from '@/components/LeadsPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, edit, index, show } from '@/routes/campaigns';
import type { Campaign, Paginated } from '@/types';

const props = defineProps<{
    campaigns: Paginated<Campaign>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
        ],
    },
});

const STATUS_LABELS: Record<Campaign['status'], string> = {
    draft: 'Rascunho',
    running: 'Em andamento',
    completed: 'Concluída',
    failed: 'Falhou',
};

function goToPage(page: number) {
    router.get(
        index.url(),
        { page },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}
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
                        <th class="px-4 py-3 font-semibold"></th>
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
                                STATUS_LABELS[campaign.status]
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
                                class="flex items-center justify-end gap-1"
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
                                class="flex items-center justify-end gap-1"
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
