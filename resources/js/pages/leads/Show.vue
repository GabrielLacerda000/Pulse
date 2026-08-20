<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AtSign, Globe, Mail, MapPin, Pencil, Phone, Star } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import LeadDeleteDialog from '@/components/leads/LeadDeleteDialog.vue';
import LeadNotesForm from '@/components/leads/LeadNotesForm.vue';
import LeadStatusBadge from '@/components/leads/LeadStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editLead, index } from '@/routes/leads';
import type { Lead } from '@/types';

const props = defineProps<{
    lead: Lead;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Leads', href: index() },
        ],
    },
});

const location = computed(() => {
    if (props.lead.city && props.lead.state) {
        return `${props.lead.city} - ${props.lead.state}`;
    }

    return props.lead.city ?? props.lead.state ?? null;
});
</script>

<template>
    <Head :title="lead.company_name" />

    <div class="flex max-w-2xl flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <Heading :title="lead.company_name" :description="lead.category ?? undefined" />

            <div class="flex shrink-0 items-center gap-1">
                <Button as-child variant="outline" class="rounded-full">
                    <Link :href="editLead(lead.id)">
                        <Pencil class="size-4" />
                        Editar
                    </Link>
                </Button>
                <LeadDeleteDialog :lead="lead" />
            </div>
        </div>

        <div class="grid gap-2 rounded-xl border border-border p-4 text-sm">
            <div v-if="lead.phone" class="flex items-center gap-2 text-foreground">
                <Phone class="size-4 text-muted-foreground" />
                {{ lead.phone }}
            </div>
            <div v-if="lead.email" class="flex items-center gap-2 text-foreground">
                <Mail class="size-4 text-muted-foreground" />
                {{ lead.email }}
            </div>
            <div v-if="lead.website" class="flex items-center gap-2 text-foreground">
                <Globe class="size-4 text-muted-foreground" />
                {{ lead.website }}
            </div>
            <div v-if="lead.instagram" class="flex items-center gap-2 text-foreground">
                <AtSign class="size-4 text-muted-foreground" />
                {{ lead.instagram }}
            </div>
            <div v-if="location" class="flex items-center gap-2 text-foreground">
                <MapPin class="size-4 text-muted-foreground" />
                {{ location }}
            </div>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase">
                Status
            </h3>
            <LeadStatusBadge :status="lead.status" />
        </div>

        <div class="space-y-3 rounded-xl border border-border p-4">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase">
                Presença digital
            </h3>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-muted-foreground">Site</dt>
                <dd>{{ lead.has_website ? 'Sim' : 'Não' }}</dd>

                <dt class="text-muted-foreground">Instagram</dt>
                <dd>{{ lead.has_instagram ? 'Sim' : 'Não' }}</dd>

                <dt class="text-muted-foreground">Score</dt>
                <dd>{{ lead.score ?? '—' }}</dd>

                <template v-if="lead.rating">
                    <dt class="text-muted-foreground">Google</dt>
                    <dd class="flex items-center gap-1">
                        <Star class="size-4 fill-yellow-500 text-yellow-500" />
                        {{ lead.rating }}
                        <span v-if="lead.reviews_count" class="text-muted-foreground"
                            >({{ lead.reviews_count }} avaliações)</span
                        >
                    </dd>
                </template>
            </dl>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase">
                Notas
            </h3>
            <LeadNotesForm :lead="lead" />
        </div>
    </div>
</template>
