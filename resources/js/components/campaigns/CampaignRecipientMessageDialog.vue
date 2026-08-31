<script setup lang="ts">
import { Eye } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { usePhoneMask } from '@/composables/usePhoneMask';
import { PULSE_WPP_ERROR_CODE_LABELS } from '@/constants/pulseWppErrorCode';
import type { CampaignRecipient } from '@/types';

const props = defineProps<{
    recipient: CampaignRecipient;
}>();

const { formatPhoneDisplay } = usePhoneMask();

const errorLabel = computed(
    () =>
        props.recipient.error_code &&
        PULSE_WPP_ERROR_CODE_LABELS[props.recipient.error_code],
);
</script>

<template>
    <Dialog v-if="recipient.status !== 'pending'">
        <DialogTrigger as-child>
            <Button variant="ghost" size="sm">
                <Eye class="size-4" />
                Ver mais
            </Button>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ recipient.company_name }}</DialogTitle>
                <DialogDescription>{{
                    formatPhoneDisplay(recipient.phone)
                }}</DialogDescription>
            </DialogHeader>

            <p
                class="rounded-lg border border-border bg-muted/50 p-3 text-sm whitespace-pre-wrap"
            >
                {{ recipient.rendered_content }}
            </p>

            <p v-if="errorLabel" class="text-sm text-destructive">
                Motivo da falha: {{ errorLabel }}
            </p>
        </DialogContent>
    </Dialog>
</template>
