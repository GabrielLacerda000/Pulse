<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Upload } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import LeadDeleteDialog from '@/components/leads/LeadDeleteDialog.vue';
import LeadMessageTemplateSelector from '@/components/leads/LeadMessageTemplateSelector.vue';
import LeadsBulkActionsBar from '@/components/leads/LeadsBulkActionsBar.vue';
import LeadsBulkDeleteDialog from '@/components/leads/LeadsBulkDeleteDialog.vue';
import LeadsFilterBar from '@/components/leads/LeadsFilterBar.vue';
import LeadSortableHeader from '@/components/leads/LeadSortableHeader.vue';
import LeadStatusBadge from '@/components/leads/LeadStatusBadge.vue';
import LeadsPagination from '@/components/LeadsPagination.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { useLeadFilters } from '@/composables/useLeadFilters';
import { useLeadSelection } from '@/composables/useLeadSelection';
import { usePhoneMask } from '@/composables/usePhoneMask';
import { dashboard } from '@/routes';
import { create, edit as editLead, index, show } from '@/routes/leads';
import { create as createImport } from '@/routes/leads/import';
import type { Lead, LeadFilterOptions, LeadFilters, Paginated } from '@/types';

const props = defineProps<{
    leads: Paginated<Lead>;
    filters: LeadFilters;
    filterOptions: LeadFilterOptions;
}>();

const { formatPhoneDisplay } = usePhoneMask();
const { filters, toggleSort, goToPage } = useLeadFilters(props.filters);

const bulkDeleteDialogOpen = ref(false);

const {
    selectedIds,
    headerCheckboxState,
    toggleAll,
    toggleRow,
    clearSelection,
} = useLeadSelection(() => props.leads.data.map((lead) => lead.id));

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Leads', href: index() },
        ],
    },
});
</script>

<template>
    <Head title="Leads" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Leads"
                description="Gerencie os leads captados e cadastrados manualmente"
            />

            <div class="flex items-center gap-2">
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="createImport()">
                        <Upload class="size-4" />
                        Importar
                    </Link>
                </Button>
                <Button as-child class="rounded-full">
                    <Link :href="create()">
                        <Plus class="size-4" />
                        Novo lead
                    </Link>
                </Button>
            </div>
        </div>

        <Transition
            mode="out-in"
            enter-active-class="animate-in fade-in-0 slide-in-from-top-2 duration-200"
            leave-active-class="animate-out fade-out-0 slide-out-to-top-2 duration-150"
        >
            <LeadsBulkActionsBar
                v-if="selectedIds.size > 0"
                key="bulk-bar"
                :count="selectedIds.size"
                @delete="bulkDeleteDialogOpen = true"
                @clear="clearSelection"
            />
            <LeadsFilterBar
                v-else
                key="filter-bar"
                v-model:search="filters.search"
                v-model:status="filters.status"
                v-model:category="filters.category"
                v-model:city="filters.city"
                :filter-options="filterOptions"
            />
        </Transition>

        <LeadsBulkDeleteDialog
            v-model:open="bulkDeleteDialogOpen"
            :ids="Array.from(selectedIds)"
            @deleted="clearSelection"
        />

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-border bg-muted text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <th class="w-10 px-4 py-3">
                            <Checkbox
                                :model-value="headerCheckboxState"
                                aria-label="Selecionar todos os leads da página"
                                class="border-muted-foreground/50"
                                @update:model-value="toggleAll"
                            />
                        </th>
                        <th class="px-4 py-3 font-semibold">
                            <LeadSortableHeader
                                column="company_name"
                                :filters="filters"
                                @sort="toggleSort"
                            >
                                Empresa
                            </LeadSortableHeader>
                        </th>
                        <th class="px-4 py-3 font-semibold">Categoria</th>
                        <th class="px-4 py-3 font-semibold">Telefone</th>
                        <th class="px-4 py-3 font-semibold">Cidade</th>
                        <th class="px-4 py-3 font-semibold">
                            <LeadSortableHeader
                                column="score"
                                :filters="filters"
                                @sort="toggleSort"
                            >
                                Score
                            </LeadSortableHeader>
                        </th>
                        <th class="px-4 py-3 font-semibold">Tipo</th>
                        <th class="px-4 py-3 font-semibold">
                            <LeadSortableHeader
                                column="status"
                                :filters="filters"
                                @sort="toggleSort"
                            >
                                Status
                            </LeadSortableHeader>
                        </th>
                        <th class="px-4 py-3 font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="leads.data.length === 0"
                        class="border-b border-border/60"
                    >
                        <td
                            colspan="9"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nenhum lead encontrado.
                        </td>
                    </tr>
                    <tr
                        v-for="lead in leads.data"
                        :key="lead.id"
                        class="border-b border-border/60"
                    >
                        <td class="px-4 py-3">
                            <Checkbox
                                :model-value="selectedIds.has(lead.id)"
                                :aria-label="`Selecionar ${lead.company_name}`"
                                @update:model-value="
                                    (checked) => toggleRow(lead.id, checked)
                                "
                            />
                        </td>
                        <td class="px-4 py-3 font-medium">
                            {{ lead.company_name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.category ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{
                                lead.phone
                                    ? formatPhoneDisplay(lead.phone)
                                    : '—'
                            }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.city ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.score ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.type ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <LeadStatusBadge :status="lead.status" />
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1">
                                <LeadMessageTemplateSelector
                                    :whatsapp-phone="lead.whatsapp_phone"
                                    :message-templates="lead.message_templates"
                                    icon-only
                                />
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Visualizar"
                                >
                                    <Link :href="show(lead.id)">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    as-child
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Editar"
                                >
                                    <Link :href="editLead(lead.id)">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <LeadDeleteDialog :lead="lead" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <LeadsPagination
            :current-page="leads.current_page"
            :last-page="leads.last_page"
            @change="goToPage"
        />
    </div>
</template>
