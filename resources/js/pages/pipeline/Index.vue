<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
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

const scrollContainer = ref<HTMLElement | null>(null);
const isDragScrolling = ref(false);
let dragStartX = 0;
let dragStartScrollLeft = 0;

function startDragScroll(event: MouseEvent): void {
    if (!scrollContainer.value || event.button !== 0) {
        return;
    }

    if ((event.target as HTMLElement).closest('[data-lead-id]')) {
        return;
    }

    isDragScrolling.value = true;
    dragStartX = event.clientX;
    dragStartScrollLeft = scrollContainer.value.scrollLeft;
}

function handleDragScroll(event: MouseEvent): void {
    if (!isDragScrolling.value || !scrollContainer.value) {
        return;
    }

    event.preventDefault();
    scrollContainer.value.scrollLeft =
        dragStartScrollLeft - (event.clientX - dragStartX);
}

function endDragScroll(): void {
    isDragScrolling.value = false;
}

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

        <div
            ref="scrollContainer"
            :class="[
                'scrollbar-hide flex cursor-grab gap-4 overflow-x-auto pb-2',
                isDragScrolling && 'cursor-grabbing select-none',
            ]"
            @mousedown="startDragScroll"
            @mousemove="handleDragScroll"
            @mouseup="endDragScroll"
            @mouseleave="endDragScroll"
        >
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
