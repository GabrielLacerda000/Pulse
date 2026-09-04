<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampaignFormFields from '@/components/campaigns/CampaignFormFields.vue';
import CampaignSelectedLeadsPanel from '@/components/campaigns/CampaignSelectedLeadsPanel.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index, update } from '@/routes/campaigns';
import type {
    CampaignForEdit,
    Lead,
    LeadFilterOptions,
    MessageTemplate,
} from '@/types';

const props = defineProps<{
    campaign: CampaignForEdit;
    leads: Lead[];
    filterOptions: LeadFilterOptions;
    messageTemplates: MessageTemplate[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
            { title: 'Editar campanha', href: '' },
        ],
    },
});

const selectedLeadIds = ref<number[]>([...props.campaign.lead_ids]);
const messages = ref<string[]>([...props.campaign.messages]);
</script>

<template>
    <Head :title="`Editar ${campaign.name}`" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-6">
        <Heading
            title="Editar campanha"
            :description="`Atualize os dados de ${campaign.name}`"
        />

        <Form
            v-bind="update.form(campaign.id)"
            class="flex flex-col gap-6 lg:grid lg:grid-cols-[1fr_320px] lg:items-start lg:gap-6"
            v-slot="{ errors, processing }"
        >
            <div class="flex flex-col gap-6">
                <CampaignFormFields
                    v-model:selected-lead-ids="selectedLeadIds"
                    v-model:messages="messages"
                    :campaign="campaign"
                    :leads="leads"
                    :filter-options="filterOptions"
                    :message-templates="messageTemplates"
                    :errors="errors"
                />
            </div>

            <CampaignSelectedLeadsPanel
                v-model:selected-lead-ids="selectedLeadIds"
                :leads="leads"
                class="lg:sticky lg:top-6"
            />

            <div class="flex items-center gap-3 lg:col-span-2">
                <Button
                    type="submit"
                    :disabled="processing || selectedLeadIds.length === 0"
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
