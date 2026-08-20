<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import LeadFormFields from '@/components/leads/LeadFormFields.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, show, update } from '@/routes/leads';
import type { Lead } from '@/types';

defineProps<{
    lead: Lead;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Leads', href: index() },
            { title: 'Editar lead', href: '' },
        ],
    },
});
</script>

<template>
    <Head :title="`Editar ${lead.company_name}`" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-6">
        <Heading
            title="Editar lead"
            :description="`Atualize os dados de ${lead.company_name}`"
        />

        <Form
            v-bind="update.form(lead.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <LeadFormFields :lead="lead" :errors="errors" />

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing" class="rounded-full">
                    Salvar alterações
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="show(lead.id)">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
