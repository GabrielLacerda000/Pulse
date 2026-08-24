<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import CampaignLeadsField from '@/components/campaigns/CampaignLeadsField.vue';
import CampaignMessagesField from '@/components/campaigns/CampaignMessagesField.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import { index, store } from '@/routes/campaigns';
import type { Lead, MessageTemplate } from '@/types';

defineProps<{
    leads: Lead[];
    messageTemplates: MessageTemplate[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Campanhas', href: index() },
            { title: 'Nova campanha', href: store() },
        ],
    },
});

const selectedLeadIds = ref<number[]>([]);
const messages = ref<string[]>(['']);
</script>

<template>
    <Head title="Nova campanha" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
        <Heading
            title="Nova campanha"
            description="Monte uma campanha de disparo em massa pelo WhatsApp."
        />

        <Form
            v-bind="store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Nome da campanha</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    placeholder="Prospecção agosto"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="delay_min_seconds"
                        >Intervalo mínimo (segundos)</Label
                    >
                    <Input
                        id="delay_min_seconds"
                        name="delay_min_seconds"
                        type="number"
                        min="30"
                        required
                        default-value="30"
                    />
                    <InputError :message="errors.delay_min_seconds" />
                </div>

                <div class="grid gap-2">
                    <Label for="delay_max_seconds"
                        >Intervalo máximo (segundos)</Label
                    >
                    <Input
                        id="delay_max_seconds"
                        name="delay_max_seconds"
                        type="number"
                        min="30"
                        required
                        default-value="90"
                    />
                    <InputError :message="errors.delay_max_seconds" />
                </div>
            </div>

            <CampaignLeadsField
                v-model="selectedLeadIds"
                :leads="leads"
                :error="errors.lead_ids"
            />

            <CampaignMessagesField
                v-model="messages"
                :message-templates="messageTemplates"
                :error="errors.messages"
            />

            <div class="flex items-center gap-3">
                <Button
                    type="submit"
                    :disabled="processing || selectedLeadIds.length === 0"
                    class="rounded-full"
                >
                    Criar campanha
                </Button>
                <Button as-child variant="secondary" class="rounded-full">
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
