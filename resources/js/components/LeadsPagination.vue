<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    currentPage: number;
    lastPage: number;
}>();

const emit = defineEmits<{
    change: [page: number];
}>();
</script>

<template>
    <div v-if="lastPage > 1" class="flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
            Página {{ currentPage }} de {{ lastPage }}
        </p>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage <= 1"
                @click="emit('change', props.currentPage - 1)"
            >
                <ChevronLeft class="size-4" />
                Anterior
            </Button>
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage >= lastPage"
                @click="emit('change', props.currentPage + 1)"
            >
                Próxima
                <ChevronRight class="size-4" />
            </Button>
        </div>
    </div>
</template>
