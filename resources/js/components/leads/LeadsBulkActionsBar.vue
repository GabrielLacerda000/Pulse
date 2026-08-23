<script setup lang="ts">
import { Trash2, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    count: number;
}>();

const emit = defineEmits<{
    delete: [];
    clear: [];
}>();
</script>

<template>
    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-full border border-border bg-muted px-4 py-2"
    >
        <span class="text-sm font-medium">
            {{ count }} lead{{ count === 1 ? '' : 's' }} selecionado{{
                count === 1 ? '' : 's'
            }}
        </span>

        <div class="flex items-center gap-2">
            <Button
                variant="destructive"
                size="sm"
                class="rounded-full"
                @click="emit('delete')"
            >
                <Trash2 class="size-4" />
                Excluir selecionados
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="rounded-full text-muted-foreground"
                @click="emit('clear')"
            >
                <X class="size-4" />
                Cancelar
            </Button>
        </div>
    </div>
</template>
