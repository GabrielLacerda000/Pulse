<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import MessageTemplateFormFields from '@/components/message-templates/MessageTemplateFormFields.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/message-templates';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Modelos de mensagem', href: index() },
            { title: 'Novo modelo', href: store() },
        ],
    },
});
</script>

<template>
    <Head title="Novo modelo de mensagem" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-6">
        <Heading
            title="Novo modelo de mensagem"
            description="Cadastre um modelo de mensagem de WhatsApp reutilizável."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <MessageTemplateFormFields :errors="errors" />

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing"
                    class="rounded-full"
                >
                    Salvar modelo
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
