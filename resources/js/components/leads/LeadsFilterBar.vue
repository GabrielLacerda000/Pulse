<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { vMaska } from 'maska/vue';
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
import { usePhoneMask } from '@/composables/usePhoneMask';
import { LEAD_STATUS_LABELS } from '@/constants/leadStatus';
import type { LeadFilterOptions, LeadFilters } from '@/types';

defineProps<{
    filterOptions: LeadFilterOptions;
}>();

const search = defineModel<LeadFilters['search']>('search', {
    default: '',
});
const status = defineModel<LeadFilters['status']>('status', {
    default: '',
});
const category = defineModel<LeadFilters['category']>('category', {
    default: '',
});
const city = defineModel<LeadFilters['city']>('city', { default: '' });
const phone = defineModel<LeadFilters['phone']>('phone', { default: '' });

const { phoneMaskOptions } = usePhoneMask();

const ALL_VALUE = 'all';

const hasActiveFilters = computed(
    () =>
        !!(
            search.value ||
            status.value ||
            category.value ||
            city.value ||
            phone.value
        ),
);

function clearFilters() {
    search.value = '';
    status.value = '';
    category.value = '';
    city.value = '';
    phone.value = '';
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
                placeholder="Buscar por empresa..."
                class="rounded-full pl-9"
            />
        </div>

        <Input
            v-model="phone"
            v-maska="phoneMaskOptions"
            type="search"
            placeholder="Buscar por telefone..."
            class="w-full max-w-xs rounded-full"
        />

        <Select
            :model-value="status || ALL_VALUE"
            @update:model-value="
                (value) =>
                    (status =
                        value === ALL_VALUE
                            ? ''
                            : (value as LeadFilters['status']))
            "
        >
            <SelectTrigger class="rounded-full" size="sm">
                <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="ALL_VALUE">Todos os status</SelectItem>
                <SelectItem
                    v-for="statusOption in filterOptions.statuses"
                    :key="statusOption"
                    :value="statusOption"
                >
                    {{ LEAD_STATUS_LABELS[statusOption] }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Select
            :model-value="category || ALL_VALUE"
            @update:model-value="
                (value) =>
                    (category = value === ALL_VALUE ? '' : (value as string))
            "
        >
            <SelectTrigger class="rounded-full" size="sm">
                <SelectValue placeholder="Categoria" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="ALL_VALUE">Todas as categorias</SelectItem>
                <SelectItem
                    v-for="categoryOption in filterOptions.categories"
                    :key="categoryOption"
                    :value="categoryOption"
                >
                    {{ categoryOption }}
                </SelectItem>
            </SelectContent>
        </Select>

        <Select
            :model-value="city || ALL_VALUE"
            @update:model-value="
                (value) => (city = value === ALL_VALUE ? '' : (value as string))
            "
        >
            <SelectTrigger class="rounded-full" size="sm">
                <SelectValue placeholder="Cidade" />
            </SelectTrigger>
            <SelectContent>
                <SelectItem :value="ALL_VALUE">Todas as cidades</SelectItem>
                <SelectItem
                    v-for="cityOption in filterOptions.cities"
                    :key="cityOption"
                    :value="cityOption"
                >
                    {{ cityOption }}
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
