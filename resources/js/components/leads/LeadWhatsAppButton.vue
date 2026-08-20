<script setup lang="ts">
import { MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { buildWhatsAppUrl } from '@/lib/whatsapp';

const props = withDefaults(
    defineProps<{
        whatsappPhone: string | null;
        iconOnly?: boolean;
    }>(),
    {
        iconOnly: false,
    },
);

const whatsappUrl = computed(() =>
    props.whatsappPhone ? buildWhatsAppUrl(props.whatsappPhone) : null,
);
</script>

<template>
    <Button
        :as="whatsappUrl ? 'a' : 'button'"
        :href="whatsappUrl ?? undefined"
        :target="whatsappUrl ? '_blank' : undefined"
        :rel="whatsappUrl ? 'noopener noreferrer' : undefined"
        :disabled="!whatsappUrl"
        :variant="iconOnly ? 'ghost' : 'outline'"
        :size="iconOnly ? 'icon-sm' : undefined"
        :class="iconOnly ? undefined : 'rounded-full'"
        :title="whatsappUrl ? 'Abrir WhatsApp' : 'Sem telefone válido para WhatsApp'"
    >
        <MessageCircle class="size-4" />
        <span v-if="!iconOnly">Abrir WhatsApp</span>
    </Button>
</template>
