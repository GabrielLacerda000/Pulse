<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampaignFormFields from '@/components/campaigns/CampaignFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/campaigns';
import type { Lead, LeadFilterOptions, MessageTemplate } from '@/types';

const props = defineProps<{
    leads: Lead[];
    filterOptions: LeadFilterOptions;
    messageTemplates: MessageTemplate[];
    preselectedLeadIds: number[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
            { title: 'Nova campanha', href: store() },
        ],
    },
});

const selectedLeadIds = ref<number[]>([...props.preselectedLeadIds]);
const messages = ref<string[]>(['']);
</script>

<template>
    <Head title="Nova campanha" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
        <Heading
            title="Nova campanha"
            description="Monte uma campanha de disparo em massa pelo WhatsApp."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <CampaignFormFields
                v-model:selected-lead-ids="selectedLeadIds"
                v-model:messages="messages"
                :leads="leads"
                :filter-options="filterOptions"
                :message-templates="messageTemplates"
                :errors="errors"
            />

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing || selectedLeadIds.length === 0"
                    class="rounded-full"
                >
                    Criar campanha
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
