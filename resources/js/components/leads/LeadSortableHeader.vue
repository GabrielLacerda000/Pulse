<script setup lang="ts">
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import { computed } from 'vue';
import type { LeadFilters, LeadSortColumn } from '@/types';

const props = defineProps<{
    column: LeadSortColumn;
    filters: LeadFilters;
}>();

const emit = defineEmits<{
    sort: [column: LeadSortColumn];
}>();

const isActive = computed(() => props.filters.sort === props.column);
</script>

<template>
    <button
        type="button"
        class="inline-flex items-center gap-1 font-semibold hover:text-foreground"
        @click="emit('sort', column)"
    >
        <slot />
        <ArrowUp
            v-if="isActive && filters.direction === 'asc'"
            class="size-3.5"
        />
        <ArrowDown
            v-else-if="isActive && filters.direction === 'desc'"
            class="size-3.5"
        />
        <ArrowUpDown v-else class="size-3.5 opacity-40" />
    </button>
</template>
