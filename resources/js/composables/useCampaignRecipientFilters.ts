import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { reactive, watch } from 'vue';
import type { CampaignRecipientFilters } from '@/types';

function toQueryParams(filters: CampaignRecipientFilters): Record<string, string> {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => !!value),
    ) as Record<string, string>;
}

export function useCampaignRecipientFilters(
    url: string,
    initialFilters: CampaignRecipientFilters,
) {
    const only = ['recipients', 'filters'];

    const filters = reactive<CampaignRecipientFilters>({
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

    watch(() => filters.search, () => applyFiltersDebounced());
    watch(() => filters.status, () => applyFilters());

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
