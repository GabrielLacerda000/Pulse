<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
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
import { destroy } from '@/routes/campaigns';
import type { Campaign } from '@/types';

defineProps<{
    campaign: Campaign;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="ghost" size="icon-sm" title="Excluir">
                <Trash2 class="size-4 text-destructive" />
            </Button>
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Excluir campanha?</DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja excluir
                    <span class="font-medium text-foreground">{{
                        campaign.name
                    }}</span
                    >? Esta ação não pode ser desfeita.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="destroy.form(campaign.id)"
                :options="{ preserveScroll: true }"
                v-slot="{ processing }"
            >
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Excluir
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
