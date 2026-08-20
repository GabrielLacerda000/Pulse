<script setup lang="ts">
import { computed } from 'vue';
import {
    LEAD_STATUS_BADGE_CLASSES,
    LEAD_STATUS_LABELS,
} from '@/constants/leadStatus';
import type { LeadStatus } from '@/types';

const props = defineProps<{
    status: LeadStatus;
}>();

const label = computed(() => LEAD_STATUS_LABELS[props.status]);
const badgeClass = computed(() => LEAD_STATUS_BADGE_CLASSES[props.status]);
</script>

<template>
    <span
        :class="[
            'inline-flex w-fit items-center rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap',
            badgeClass,
        ]"
    >
        {{ label }}
    </span>
</template>
