<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { AtSign, Globe, Mail, MapPin, Pencil, Phone, Star } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import LeadDeleteDialog from '@/components/leads/LeadDeleteDialog.vue';
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

    <div class="flex max-w-2xl flex-col gap-6">
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

        <div class="grid gap-2 rounded-xl border border-[#eef0f3] p-4 text-sm">
            <div v-if="lead.phone" class="flex items-center gap-2 text-[#1c1c1e]">
                <Phone class="size-4 text-[#6b6f7e]" />
                {{ lead.phone }}
            </div>
            <div v-if="lead.email" class="flex items-center gap-2 text-[#1c1c1e]">
                <Mail class="size-4 text-[#6b6f7e]" />
                {{ lead.email }}
            </div>
            <div v-if="lead.website" class="flex items-center gap-2 text-[#1c1c1e]">
                <Globe class="size-4 text-[#6b6f7e]" />
                {{ lead.website }}
            </div>
            <div v-if="lead.instagram" class="flex items-center gap-2 text-[#1c1c1e]">
                <AtSign class="size-4 text-[#6b6f7e]" />
                {{ lead.instagram }}
            </div>
            <div v-if="location" class="flex items-center gap-2 text-[#1c1c1e]">
                <MapPin class="size-4 text-[#6b6f7e]" />
                {{ location }}
            </div>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-[#6b6f7e] uppercase">
                Status
            </h3>
            <LeadStatusBadge :status="lead.status" />
        </div>

        <div class="space-y-3 rounded-xl border border-[#eef0f3] p-4">
            <h3 class="text-sm font-semibold text-[#6b6f7e] uppercase">
                Presença digital
            </h3>
            <dl class="grid grid-cols-2 gap-y-2 text-sm">
                <dt class="text-[#6b6f7e]">Site</dt>
                <dd>{{ lead.has_website ? 'Sim' : 'Não' }}</dd>

                <dt class="text-[#6b6f7e]">Instagram</dt>
                <dd>{{ lead.has_instagram ? 'Sim' : 'Não' }}</dd>

                <dt class="text-[#6b6f7e]">Score</dt>
                <dd>{{ lead.score ?? '—' }}</dd>

                <template v-if="lead.rating">
                    <dt class="text-[#6b6f7e]">Google</dt>
                    <dd class="flex items-center gap-1">
                        <Star class="size-4 fill-[#fcb900] text-[#fcb900]" />
                        {{ lead.rating }}
                        <span v-if="lead.reviews_count" class="text-[#8e91a0]"
                            >({{ lead.reviews_count }} avaliações)</span
                        >
                    </dd>
                </template>
            </dl>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-[#6b6f7e] uppercase">
                Notas
            </h3>
            <div
                class="min-h-16 rounded-xl border border-[#eef0f3] bg-[#fafbfc] p-4 text-sm whitespace-pre-wrap"
            >
                {{ lead.notes || 'Nenhuma nota registrada.' }}
            </div>
        </div>
    </div>
</template>
