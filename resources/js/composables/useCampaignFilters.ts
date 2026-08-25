import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';
import { index } from '@/routes/campaigns';
import type { CampaignFilters } from '@/types';

function toQueryParams(filters: CampaignFilters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => !!value),
    ) as Record<string, string>;
}

export function useCampaignFilters(
    initialFilters: CampaignFilters,
    options?: { url?: string; only?: string[] },
) {
    const url = options?.url ?? index.url();
    const only = options?.only ?? ['campaigns', 'filters'];

    const filters = reactive<CampaignFilters>({
        search: initialFilters.search ?? '',
        status: initialFilters.status ?? '',
    });

    function applyFilters() {
        router.get(url, toQueryParams(filters), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only,
        });
    }

    const applyFiltersDebounced = useDebounceFn(applyFilters, 300);

    watch(
        () => filters.search,
        () => applyFiltersDebounced(),
    );

    watch(
        () => filters.status,
        () => applyFilters(),
    );

    function goToPage(page: number) {
        router.get(
            url,
            { ...toQueryParams(filters), page },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
            },
        );
    }

    return {
        filters,
        goToPage,
    };
}
