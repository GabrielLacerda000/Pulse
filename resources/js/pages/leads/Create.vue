<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import LeadFormFields from '@/components/leads/LeadFormFields.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/leads';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Leads', href: index() },
            { title: 'Novo lead', href: store() },
        ],
    },
});
</script>

<template>
    <Head title="Novo lead" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-6">
        <Heading
            title="Novo lead"
            description="Cadastre um lead manualmente. O status inicial será Novo."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <LeadFormFields :errors="errors" />

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing" class="rounded-full">
                    Salvar lead
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
