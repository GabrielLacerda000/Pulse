<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { bulkDestroy } from '@/routes/leads';

const open = defineModel<boolean>('open', { default: false });

const { ids } = defineProps<{
    ids: number[];
}>();

const emit = defineEmits<{
    deleted: [];
}>();

function handleSuccess() {
    open.value = false;
    emit('deleted');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>Excluir leads selecionados?</DialogTitle>
                <DialogDescription>
                    Tem certeza que deseja excluir
                    <span class="font-medium text-foreground"
                        >{{ ids.length }} lead{{
                            ids.length === 1 ? '' : 's'
                        }}</span
                    >? Esta ação não pode ser desfeita.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="bulkDestroy.form()"
                :options="{ preserveScroll: true }"
                :on-success="handleSuccess"
                v-slot="{ processing }"
            >
                <input
                    v-for="id in ids"
                    :key="id"
                    type="hidden"
                    name="ids[]"
                    :value="id"
                />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing || ids.length === 0"
                    >
                        Excluir
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
