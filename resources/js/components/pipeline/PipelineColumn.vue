<script setup lang="ts">
import { ref } from 'vue';
import type { SortableEvent } from 'vue-draggable-plus';
import { VueDraggable } from 'vue-draggable-plus';
import PipelineLeadCard from '@/components/pipeline/PipelineLeadCard.vue';
import { Button } from '@/components/ui/button';
import {
    LEAD_STATUS_BADGE_CLASSES,
    LEAD_STATUS_LABELS,
} from '@/constants/leadStatus';
import type { Lead, LeadStatus } from '@/types';

const props = defineProps<{
    status: LeadStatus;
    modelValue: Lead[];
}>();

const emit = defineEmits<{
    'update:modelValue': [leads: Lead[]];
    leadDropped: [event: SortableEvent];
}>();

const INITIAL_VISIBLE = 15;
const CARD_HEIGHT_PX = 96;

const expanded = ref(false);
</script>

<template>
    <div
        class="flex w-72 shrink-0 flex-col gap-3 rounded-xl border border-border bg-muted/40 p-3"
    >
        <div
            :class="[
                'flex items-center justify-between rounded-lg px-2 py-1.5',
                LEAD_STATUS_BADGE_CLASSES[props.status],
            ]"
        >
            <h3 class="text-sm font-semibold">
                {{ LEAD_STATUS_LABELS[props.status] }}
            </h3>
            <span
                class="rounded-full bg-background/60 px-2 py-0.5 text-xs font-medium text-current"
            >
                {{ modelValue.length }}
            </span>
        </div>

        <div
            :style="
                !expanded && modelValue.length > INITIAL_VISIBLE
                    ? { maxHeight: `${INITIAL_VISIBLE * CARD_HEIGHT_PX}px` }
                    : undefined
            "
            class="overflow-hidden"
        >
            <VueDraggable
                :model-value="modelValue"
                :animation="150"
                group="pipeline-leads"
                ghost-class="opacity-40"
                class="flex min-h-16 flex-col gap-2"
                @update:model-value="emit('update:modelValue', $event as Lead[])"
                @add="emit('leadDropped', $event)"
            >
                <PipelineLeadCard
                    v-for="lead in modelValue"
                    :key="lead.id"
                    :data-lead-id="lead.id"
                    :lead="lead"
                />
            </VueDraggable>
        </div>

        <p
            v-if="modelValue.length === 0"
            class="px-1 text-xs text-muted-foreground"
        >
            Nenhum lead nesta etapa.
        </p>

        <Button
            v-if="!expanded && modelValue.length > INITIAL_VISIBLE"
            variant="ghost"
            size="sm"
            class="rounded-full text-muted-foreground"
            @click="expanded = true"
        >
            Carregar mais ({{ modelValue.length - INITIAL_VISIBLE }})
        </Button>
    </div>
</template>
