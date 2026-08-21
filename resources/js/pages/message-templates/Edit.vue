<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import MessageTemplateFormFields from '@/components/message-templates/MessageTemplateFormFields.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/message-templates';
import type { MessageTemplate } from '@/types';

const props = defineProps<{
    messageTemplate: MessageTemplate;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Modelos de mensagem', href: index() },
            { title: 'Editar modelo', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`Editar ${props.messageTemplate.name}`" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-6">
        <Heading
            title="Editar modelo de mensagem"
            :description="`Atualize o modelo ${props.messageTemplate.name}`"
        />

        <Form
            v-bind="update.form(props.messageTemplate.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <MessageTemplateFormFields
                :message-template="props.messageTemplate"
                :errors="errors"
            />

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing"
                    class="rounded-full"
                >
                    Salvar alterações
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
