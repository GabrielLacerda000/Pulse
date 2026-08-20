<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import type { SortableEvent } from 'vue-draggable-plus';
import Heading from '@/components/Heading.vue';
import PipelineColumn from '@/components/pipeline/PipelineColumn.vue';
import { LEAD_STATUS_ORDER } from '@/constants/leadStatus';
import { dashboard } from '@/routes';
import { update as updateLeadStatus } from '@/routes/leads/status';
import { index as pipelineIndex } from '@/routes/pipeline';
import type { Lead, LeadStatus } from '@/types';

const props = defineProps<{
    leads: Lead[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Pipeline', href: pipelineIndex() },
        ],
    },
});

function groupLeadsByStatus(leads: Lead[]): Record<LeadStatus, Lead[]> {
    return Object.fromEntries(
        LEAD_STATUS_ORDER.map((status) => [
            status,
            leads.filter((lead) => lead.status === status),
        ]),
    ) as Record<LeadStatus, Lead[]>;
}

const leadsByStatus = reactive<Record<LeadStatus, Lead[]>>(
    groupLeadsByStatus(props.leads),
);

function handleLeadDropped(newStatus: LeadStatus, event: SortableEvent): void {
    const leadId = Number(event.item.dataset.leadId);

    if (!leadId) {
        return;
    }

    const lead = leadsByStatus[newStatus].find(
        (candidate) => candidate.id === leadId,
    );
    const previousStatus = lead?.status;

    if (!lead || !previousStatus) {
        return;
    }

    router.patch(
        updateLeadStatus(leadId).url,
        { status: newStatus },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                lead.status = newStatus;
            },
            onError: () => {
                const index = leadsByStatus[newStatus].indexOf(lead);

                if (index !== -1) {
                    leadsByStatus[newStatus].splice(index, 1);
                    leadsByStatus[previousStatus].push(lead);
                }
            },
        },
    );
}
</script>

<template>
    <Head title="Pipeline" />

    <div class="flex flex-col gap-6 p-6">
        <Heading
            title="Pipeline"
            description="Arraste os leads entre as etapas para atualizar o status comercial"
        />

        <div class="flex gap-4 overflow-x-auto pb-2">
            <PipelineColumn
                v-for="status in LEAD_STATUS_ORDER"
                :key="status"
                :status="status"
                v-model="leadsByStatus[status]"
                @lead-dropped="(event) => handleLeadDropped(status, event)"
            />
        </div>
    </div>
</template>
