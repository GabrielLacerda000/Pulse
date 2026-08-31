<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Play } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { start } from '@/routes/campaigns';
import type { Campaign } from '@/types';

defineProps<{
    campaign: Campaign;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button
                variant="ghost"
                size="icon-sm"
                title="Iniciar"
                class="cursor-pointer"
            >
                <Play class="size-4" />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Iniciar campanha?</DialogTitle>
                <DialogDescription>
                    Iniciar
                    <span class="font-medium text-foreground">{{
                        campaign.name
                    }}</span
                    >? Isso vai começar o envio real das mensagens.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="start.form(campaign.id)"
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button type="submit" :disabled="processing">
                        Iniciar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
