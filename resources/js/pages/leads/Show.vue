<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AtSign,
    Globe,
    Link as LinkIcon,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Star,
} from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import LeadDeleteDialog from '@/components/leads/LeadDeleteDialog.vue';
import LeadMessageTemplateSelector from '@/components/leads/LeadMessageTemplateSelector.vue';
import LeadNotesForm from '@/components/leads/LeadNotesForm.vue';
import LeadStatusBadge from '@/components/leads/LeadStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editLead, index } from '@/routes/leads';
import type { Lead, RenderedMessageTemplate } from '@/types';

const props = defineProps<{
    lead: Lead;
    messageTemplates: RenderedMessageTemplate[];
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

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                :title="lead.company_name"
                :description="lead.category ?? undefined"
            />

            <div class="flex shrink-0 items-center gap-1">
                <LeadMessageTemplateSelector
                    :whatsapp-phone="lead.whatsapp_phone"
                    :message-templates="messageTemplates"
                />
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
            <div
                v-if="lead.phone"
                class="flex items-center gap-2 text-foreground"
            >
                <Phone class="size-4 text-muted-foreground" />
                {{ lead.phone }}
            </div>
            <div
                v-if="lead.email"
                class="flex items-center gap-2 text-foreground"
            >
                <Mail class="size-4 text-muted-foreground" />
                {{ lead.email }}
            </div>
            <div
                v-if="lead.website"
                class="flex min-w-0 items-center gap-2 text-foreground"
            >
                <Globe class="size-4 shrink-0 text-muted-foreground" />
                <a
                    :href="lead.website"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="min-w-0 truncate text-primary underline-offset-4 hover:underline"
                >
                    {{ lead.website }}
                </a>
            </div>
            <div
                v-if="lead.instagram"
                class="flex items-center gap-2 text-foreground"
            >
                <AtSign class="size-4 text-muted-foreground" />
                {{ lead.instagram }}
            </div>
            <div
                v-if="lead.facebook"
                class="flex min-w-0 items-center gap-2 text-foreground"
            >
                <LinkIcon class="size-4 shrink-0 text-muted-foreground" />
                <a
                    :href="lead.facebook"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="min-w-0 truncate text-primary underline-offset-4 hover:underline"
                >
                    {{ lead.facebook }}
                </a>
            </div>
            <div
                v-if="location"
                class="flex items-center gap-2 text-foreground"
            >
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
                        <span
                            v-if="lead.reviews_count"
                            class="text-muted-foreground"
                            >({{ lead.reviews_count }} avaliações)</span
                        >
                    </dd>
                </template>
            </dl>
        </div>

        <div v-if="lead.summary" class="space-y-2">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase">
                Resumo
            </h3>
            <p class="text-sm whitespace-pre-line text-foreground">
                {{ lead.summary }}
            </p>
        </div>

        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-muted-foreground uppercase">
                Notas
            </h3>
            <LeadNotesForm :lead="lead" />
        </div>
    </div>
</template>
