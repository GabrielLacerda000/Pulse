<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    CAMPAIGN_STATUS_LABELS,
    CAMPAIGN_STATUS_ORDER,
} from '@/constants/campaignStatus';
import type { CampaignFilters } from '@/types';

const search = defineModel<CampaignFilters['search']>('search', {
    default: '',
});
const status = defineModel<CampaignFilters['status']>('status', {
    default: '',
});

const ALL_VALUE = 'all';

const hasActiveFilters = computed(() => !!(search.value || status.value));

function clearFilters() {
    search.value = '';
    status.value = '';
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative w-full max-w-xs">
            <Search
                class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
            />
            <Input
                v-model="search"
                type="search"
                placeholder="Buscar por nome..."
                class="rounded-full pl-9"
            />
        </div>

        <Select
            :model-value="status || ALL_VALUE"
            @update:model-value="
                (value) =>
                    (status =
                        value === ALL_VALUE
                            ? ''
                            : (value as CampaignFilters['status']))
            "
        >
            <SelectTrigger class="rounded-full" size="sm">
                <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="ALL_VALUE">Todos os status</SelectItem>
                <SelectItem
                    v-for="statusOption in CAMPAIGN_STATUS_ORDER"
                    :key="statusOption"
                    :value="statusOption"
                >
                    {{ CAMPAIGN_STATUS_LABELS[statusOption] }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Button
            v-if="hasActiveFilters"
            variant="ghost"
            size="sm"
            class="rounded-full text-muted-foreground"
            @click="clearFilters"
        >
            <X class="size-4" />
            Limpar filtros
        </Button>
    </div>
</template>
