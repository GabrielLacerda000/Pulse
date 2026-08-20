<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye } from '@lucide/vue';
import LeadWhatsAppButton from '@/components/leads/LeadWhatsAppButton.vue';
import { Button } from '@/components/ui/button';
import { show } from '@/routes/leads';
import type { Lead } from '@/types';

defineProps<{
    lead: Lead;
}>();
</script>

<template>
    <div
        class="flex cursor-grab flex-col gap-2 rounded-xl border border-border bg-card p-3 text-sm shadow-sm active:cursor-grabbing"
    >
        <p class="font-medium text-foreground">{{ lead.company_name }}</p>

        <p v-if="lead.category" class="text-xs text-muted-foreground">
            {{ lead.category }}
        </p>

        <p class="text-xs text-muted-foreground">
            {{ lead.phone ?? 'Sem telefone' }}
        </p>

        <div class="flex items-center justify-between pt-1">
            <span
                class="rounded-full bg-muted px-2 py-0.5 text-xs font-semibold text-muted-foreground"
            >
                Score: {{ lead.score ?? '—' }}
            </span>

            <div class="flex items-center gap-1">
                <LeadWhatsAppButton
                    :whatsapp-phone="lead.whatsapp_phone"
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
            </div>
        </div>
    </div>
</template>
