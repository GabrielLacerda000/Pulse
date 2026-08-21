import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';
import { index } from '@/routes/leads';
import type { LeadFilters, LeadSortColumn } from '@/types';

function toQueryParams(filters: LeadFilters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => !!value),
    ) as Record<string, string>;
}

export function useLeadFilters(initialFilters: LeadFilters) {
    const filters = reactive<LeadFilters>({
        search: initialFilters.search ?? '',
        status: initialFilters.status ?? '',
        category: initialFilters.category ?? '',
        city: initialFilters.city ?? '',
        sort: initialFilters.sort ?? '',
        direction: initialFilters.direction ?? '',
    });

    function applyFilters() {
        router.get(index.url(), toQueryParams(filters), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['leads', 'filters'],
        });
    }

    const applyFiltersDebounced = useDebounceFn(applyFilters, 300);

    watch(
        () => filters.search,
        () => applyFiltersDebounced(),
    );

    watch(
        [() => filters.status, () => filters.category, () => filters.city],
        () => applyFilters(),
    );

    function toggleSort(column: LeadSortColumn) {
        if (filters.sort === column) {
            filters.direction = filters.direction === 'asc' ? 'desc' : 'asc';
        } else {
            filters.sort = column;
            filters.direction = 'asc';
        }

        applyFilters();
    }

    return {
        filters,
        toggleSort,
    };
}
