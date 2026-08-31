<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { update as updateNotes } from '@/routes/leads/notes';
import type { Lead } from '@/types';

defineProps<{
    lead: Lead;
}>();
</script>

<template>
    <Form
        v-bind="updateNotes.form(lead.id)"
        :options="{ preserveScroll: true }"
        v-slot="{ errors, processing }"
        class="space-y-3"
    >
        <Textarea
            id="notes"
            name="notes"
            :default-value="lead.notes ?? ''"
            placeholder="Observações sobre o prospect..."
            class="min-h-24"
        />
        <InputError :message="errors.notes" />

        <Button
            type="submit"
            :disabled="processing"
            size="sm"
            class="rounded-full"
        >
            Salvar notas
        </Button>
    </Form>
</template>
