<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LeadImportSummary from '@/components/leads/LeadImportSummary.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index } from '@/routes/leads';
import { create, preview as previewRoute, store } from '@/routes/leads/import';
import type { LeadImportPreview } from '@/types';

const props = defineProps<{
    preview?: LeadImportPreview | null;
}>();

const importableCount = computed(
    () => (props.preview?.total ?? 0) - (props.preview?.invalid ?? 0),
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Leads', href: index() },
            { title: 'Importar leads', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Importar leads" />

    <div class="mx-auto flex w-full max-w-xl flex-col gap-6 p-6">
        <Heading
            title="Importar leads"
            description="Envie uma planilha .xlsx ou .csv exportada do sistema de captação."
        />

        <Form
            v-if="!props.preview"
            v-bind="previewRoute.form()"
            class="space-y-4"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <input
                    id="file"
                    type="file"
                    name="file"
                    accept=".xlsx,.csv"
                    required
                    class="rounded-md border border-input bg-transparent px-3 py-2 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-muted file:px-3 file:py-1.5 file:text-sm file:font-medium"
                />
                <InputError :message="errors.file" />
            </div>

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing"
                    class="rounded-full"
                >
                    <Upload class="size-4" />
                    Enviar planilha
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>

        <template v-else>
            <LeadImportSummary :preview="props.preview" />

            <div class="flex items-center gap-3">
                <Form v-bind="store.form()" v-slot="{ processing }">
                    <Button
                        type="submit"
                        :disabled="processing || importableCount === 0"
                        class="rounded-full"
                    >
                        Importar {{ importableCount }} leads
                    </Button>
                </Form>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="create()">Escolher outro arquivo</Link>
                </Button>
            </div>
        </template>
    </div>
</template>
