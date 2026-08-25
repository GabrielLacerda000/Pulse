<script setup lang="ts">
import { computed, reactive } from 'vue';
import CampaignLeadsField from '@/components/campaigns/CampaignLeadsField.vue';
import CampaignMessagesField from '@/components/campaigns/CampaignMessagesField.vue';
import InputError from '@/components/InputError.vue';
import LeadsFilterBar from '@/components/leads/LeadsFilterBar.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePhoneMask } from '@/composables/usePhoneMask';
import type {
    CampaignForEdit,
    Lead,
    LeadFilterOptions,
    LeadFilters,
    MessageTemplate,
} from '@/types';

const props = withDefaults(
    defineProps<{
        campaign?: Partial<CampaignForEdit>;
        leads: Lead[];
        filterOptions: LeadFilterOptions;
        messageTemplates: MessageTemplate[];
        errors?: Partial<
            Record<'name' | 'delay_min_seconds' | 'delay_max_seconds' | 'lead_ids' | 'messages', string>
        >;
    }>(),
    {
        campaign: () => ({}),
        errors: () => ({}),
    },
);

const selectedLeadIds = defineModel<number[]>('selectedLeadIds', {
    default: () => [],
});
const messages = defineModel<string[]>('messages', { default: () => [''] });

const { stripPhoneDigits } = usePhoneMask();

const leadFilters = reactive<
    Pick<LeadFilters, 'search' | 'status' | 'category' | 'city' | 'phone'>
>({
    search: '',
    status: '',
    category: '',
    city: '',
    phone: '',
});

const filteredLeads = computed(() =>
    props.leads.filter((lead) => {
        if (
            leadFilters.search &&
            !lead.company_name
                .toLowerCase()
                .includes(leadFilters.search.toLowerCase())
        ) {
            return false;
        }

        if (leadFilters.status && lead.status !== leadFilters.status) {
            return false;
        }

        if (leadFilters.category && lead.category !== leadFilters.category) {
            return false;
        }

        if (leadFilters.city && lead.city !== leadFilters.city) {
            return false;
        }

        if (
            leadFilters.phone &&
            !stripPhoneDigits(lead.phone).includes(
                stripPhoneDigits(leadFilters.phone),
            )
        ) {
            return false;
        }

        return true;
    }),
);

</script>

<template>
    <div class="grid gap-2">
        <Label for="name">Nome da campanha</Label>
        <Input
            id="name"
            name="name"
            :default-value="campaign.name ?? ''"
            required
            placeholder="Prospecção agosto"
        />
        <InputError :message="errors.name" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="grid gap-2">
            <Label for="delay_min_seconds">Intervalo mínimo (segundos)</Label>
            <Input
                id="delay_min_seconds"
                name="delay_min_seconds"
                type="number"
                min="30"
                required
                :default-value="campaign.delay_min_seconds ?? 30"
            />
            <InputError :message="errors.delay_min_seconds" />
        </div>

        <div class="grid gap-2">
            <Label for="delay_max_seconds">Intervalo máximo (segundos)</Label>
            <Input
                id="delay_max_seconds"
                name="delay_max_seconds"
                type="number"
                min="30"
                required
                :default-value="campaign.delay_max_seconds ?? 90"
            />
            <InputError :message="errors.delay_max_seconds" />
        </div>
    </div>

    <LeadsFilterBar
        v-model:search="leadFilters.search"
        v-model:status="leadFilters.status"
        v-model:category="leadFilters.category"
        v-model:city="leadFilters.city"
        v-model:phone="leadFilters.phone"
        :filter-options="filterOptions"
    />

    <CampaignLeadsField
        v-model="selectedLeadIds"
        :leads="filteredLeads"
        :error="errors.lead_ids"
    />

    <CampaignMessagesField
        v-model="messages"
        :message-templates="messageTemplates"
        :error="errors.messages"
    />
</template>
