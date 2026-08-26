<script setup lang="ts">
import { computed } from 'vue';
import {
    CAMPAIGN_RECIPIENT_STATUS_BADGE_CLASSES,
    CAMPAIGN_RECIPIENT_STATUS_LABELS,
} from '@/constants/campaignRecipientStatus';
import type { CampaignRecipientStatus } from '@/types';

const props = defineProps<{
    status: CampaignRecipientStatus;
}>();

const label = computed(() => CAMPAIGN_RECIPIENT_STATUS_LABELS[props.status]);
const badgeClass = computed(
    () => CAMPAIGN_RECIPIENT_STATUS_BADGE_CLASSES[props.status],
);
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
