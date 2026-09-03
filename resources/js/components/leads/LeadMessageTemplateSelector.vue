<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronDown, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { buildWhatsAppUrl } from '@/lib/whatsapp';
import { create as createMessageTemplate } from '@/routes/message-templates';
import type { RenderedMessageTemplate } from '@/types';

const props = withDefaults(
    defineProps<{
        whatsappPhone: string | null;
        messageTemplates: RenderedMessageTemplate[];
        iconOnly?: boolean;
    }>(),
    {
        iconOnly: false,
    },
);

const templateUrls = computed(() => {
    const phone = props.whatsappPhone;

    if (!phone) {
        return [];
    }

    return props.messageTemplates.map((template) => ({
        ...template,
        whatsappUrl: buildWhatsAppUrl(phone, template.renderedContent),
    }));
});
</script>

<template>
    <div
        v-if="messageTemplates.length === 0"
        :class="[
            'flex items-center gap-2 text-sm text-muted-foreground',
            iconOnly && 'whitespace-nowrap',
        ]"
    >
        <span v-if="!iconOnly">Nenhum modelo de mensagem cadastrado.</span>
        <Link
            :href="createMessageTemplate()"
            class="text-primary underline-offset-4 hover:underline"
            :title="
                iconOnly
                    ? 'Nenhum modelo de mensagem cadastrado. Criar modelo'
                    : undefined
            "
        >
            Criar modelo
        </Link>
    </div>

    <DropdownMenu v-else>
        <DropdownMenuTrigger as-child>
            <Button
                :variant="iconOnly ? 'ghost' : 'outline'"
                :size="iconOnly ? 'icon-sm' : undefined"
                :class="
                    iconOnly
                        ? 'text-green-700 hover:bg-green-100 hover:text-green-800 dark:text-green-400 dark:hover:bg-green-950 dark:hover:text-green-300'
                        : 'rounded-full border-2 border-green-400 bg-green-700 text-white hover:bg-green-800 dark:border-green-500 dark:bg-green-800 dark:hover:bg-green-700'
                "
                :disabled="!whatsappPhone"
                :title="
                    whatsappPhone
                        ? 'Abrir WhatsApp'
                        : 'Sem telefone válido para WhatsApp'
                "
            >
                <MessageCircle class="size-4" />
                <template v-if="!iconOnly">
                    Abrir WhatsApp
                    <ChevronDown class="size-4" />
                </template>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="template in templateUrls"
                :key="template.id"
                as-child
            >
                <a
                    :href="template.whatsappUrl"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="block w-full cursor-pointer"
                >
                    {{ template.name }}
                </a>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
