<script setup lang="ts">
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { usePhoneMask } from '@/composables/usePhoneMask';
import type { Lead } from '@/types';

const props = defineProps<{
    leads: Lead[];
    error?: string;
}>();

const selected = defineModel<number[]>({ default: () => [] });

const { formatPhoneDisplay } = usePhoneMask();

const allSelected = computed(
    () =>
        props.leads.length > 0 && selected.value.length === props.leads.length,
);

function toggle(leadId: number, checked: boolean) {
    selected.value = checked
        ? [...selected.value, leadId]
        : selected.value.filter((id) => id !== leadId);
}

function toggleAll(checked: boolean) {
    selected.value = checked ? props.leads.map((lead) => lead.id) : [];
}
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center justify-between">
            <Label>Leads ({{ selected.length }} selecionados)</Label>
            <button
                type="button"
                class="text-sm text-muted-foreground hover:text-foreground"
                @click="toggleAll(!allSelected)"
            >
                {{ allSelected ? 'Desmarcar todos' : 'Selecionar todos' }}
            </button>
        </div>

        <div class="max-h-64 overflow-y-auto rounded-md border border-border">
            <p
                v-if="leads.length === 0"
                class="px-3 py-4 text-sm text-muted-foreground"
            >
                Nenhum lead com telefone válido para WhatsApp foi encontrado.
            </p>
            <label
                v-for="lead in leads"
                :key="lead.id"
                class="flex cursor-pointer items-center gap-3 border-b border-border/60 px-3 py-2 text-sm last:border-b-0 hover:bg-muted"
            >
                <Checkbox
                    :model-value="selected.includes(lead.id)"
                    @update:model-value="
                        (checked) => toggle(lead.id, checked === true)
                    "
                />
                <span class="font-medium">{{ lead.company_name }}</span>
                <span class="text-muted-foreground">{{
                    formatPhoneDisplay(lead.whatsapp_phone)
                }}</span>
            </label>
        </div>

        <input
            v-for="leadId in selected"
            :key="leadId"
            type="hidden"
            name="lead_ids[]"
            :value="leadId"
        />
        <InputError :message="error" />
    </div>
</template>
