<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Pencil, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import LeadDeleteDialog from '@/components/leads/LeadDeleteDialog.vue';
import LeadStatusBadge from '@/components/leads/LeadStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { create, edit as editLead, index, show } from '@/routes/leads';
import type { Lead } from '@/types';

defineProps<{
    leads: Lead[];
}>();

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

            <Button as-child class="rounded-full">
                <Link :href="create()">
                    <Plus class="size-4" />
                    Novo lead
                </Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-border">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr
                        class="border-b border-border bg-muted text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                    >
                        <th class="px-4 py-3 font-semibold">Empresa</th>
                        <th class="px-4 py-3 font-semibold">Categoria</th>
                        <th class="px-4 py-3 font-semibold">Telefone</th>
                        <th class="px-4 py-3 font-semibold">Cidade</th>
                        <th class="px-4 py-3 font-semibold">Score</th>
                        <th class="px-4 py-3 font-semibold">Tipo</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-if="leads.length === 0"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td
                            colspan="8"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            Nenhum lead cadastrado ainda.
                        </td>
                    </tr>
                    <tr
                        v-for="lead in leads"
                        :key="lead.id"
                        class="border-b border-border/60 last:border-b-0"
                    >
                        <td class="px-4 py-3 font-medium">
                            {{ lead.company_name }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.category ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ lead.phone ?? '—' }}
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
    </div>
</template>
