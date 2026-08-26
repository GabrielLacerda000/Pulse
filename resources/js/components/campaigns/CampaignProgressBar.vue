<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import { Progress } from '@/components/ui/progress';
import { progress as progressRoute } from '@/routes/campaigns';
import type { CampaignProgress } from '@/types';

const props = defineProps<{
    campaignId: number;
    initialProgress: CampaignProgress;
    pollIntervalMs?: number;
}>();

const emit = defineEmits<{
    update: [progress: CampaignProgress];
}>();

const progress = ref<CampaignProgress>(props.initialProgress);

const percentage = () =>
    progress.value.total === 0
        ? 0
        : Math.round(
              ((progress.value.sent + progress.value.failed) /
                  progress.value.total) *
                  100,
          );

let pollTimer: ReturnType<typeof setTimeout> | null = null;

async function poll() {
    try {
        const response = await fetch(
            progressRoute.url({ campaign: props.campaignId }),
            {
                headers: { Accept: 'application/json' },
            },
        );
        const data: CampaignProgress = await response.json();

        progress.value = data;
        emit('update', data);

        if (data.status !== 'running') {
            return;
        }
    } catch {
        // A transient network error shouldn't stop polling; try again next tick.
    }

    schedulePoll();
}

function schedulePoll() {
    pollTimer = setTimeout(poll, props.pollIntervalMs ?? 3000);
}

if (progress.value.status === 'running') {
    schedulePoll();
}

onUnmounted(() => {
    if (pollTimer) {
        clearTimeout(pollTimer);
    }
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            class="flex items-center justify-between text-sm text-muted-foreground"
        >
            <span
                >{{ progress.sent + progress.failed }} de
                {{ progress.total }} enviadas</span
            >
            <span>{{ percentage() }}%</span>
        </div>
        <Progress
            :model-value="percentage()"
            :indicator-class="
                progress.status === 'running'
                    ? 'bg-green-500 animate-pulse'
                    : undefined
            "
        />
    </div>
</template>
