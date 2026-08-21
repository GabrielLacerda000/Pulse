<script setup lang="ts">
import type { LeadImportPreview } from '@/types';

defineProps<{
    preview: LeadImportPreview;
}>();
</script>

<template>
    <div class="space-y-3">
        <div class="grid grid-cols-3 gap-3">
            <div class="rounded-xl border border-border p-4">
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Novos
                </p>
                <p class="mt-1 text-2xl font-semibold">{{ preview.new }}</p>
            </div>
            <div class="rounded-xl border border-border p-4">
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Duplicados
                </p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ preview.duplicated }}
                </p>
            </div>
            <div class="rounded-xl border border-border p-4">
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Inválidos
                </p>
                <p class="mt-1 text-2xl font-semibold text-destructive">
                    {{ preview.invalid }}
                </p>
            </div>
        </div>
        <p class="text-sm text-muted-foreground">
            {{ preview.total }} linha(s) encontrada(s) na planilha.
        </p>
    </div>
</template>
