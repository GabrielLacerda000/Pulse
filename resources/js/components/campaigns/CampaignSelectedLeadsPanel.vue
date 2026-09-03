<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Lead } from '@/types';

const props = defineProps<{
    leads: Lead[];
}>();

const selectedLeadIds = defineModel<number[]>('selectedLeadIds', {
    default: () => [],
});

const selectedLeads = computed(() =>
    props.leads.filter((lead) => selectedLeadIds.value.includes(lead.id)),
);

function remove(leadId: number) {
    selectedLeadIds.value = selectedLeadIds.value.filter((id) => id !== leadId);
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle
                >Leads selecionados ({{ selectedLeadIds.length }})</CardTitle
            >
        </CardHeader>
        <CardContent>
            <p
                v-if="selectedLeads.length === 0"
                class="text-sm text-muted-foreground"
            >
                Nenhum lead selecionado ainda.
            </p>
            <ul v-else class="flex max-h-[60vh] flex-col gap-1 overflow-y-auto">
                <li
                    v-for="lead in selectedLeads"
                    :key="lead.id"
                    class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                >
                    <span class="truncate font-medium">{{
                        lead.company_name
                    }}</span>
                    <button
                        type="button"
                        class="shrink-0 rounded-sm p-1 text-muted-foreground hover:bg-accent hover:text-foreground"
                        :title="`Remover ${lead.company_name}`"
                        @click="remove(lead.id)"
                    >
                        <X class="size-3.5" />
                    </button>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
