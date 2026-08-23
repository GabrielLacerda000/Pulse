import { computed, reactive, watch } from 'vue';

export function useLeadSelection(getPageIds: () => number[]) {
    const selectedIds = reactive(new Set<number>());

    const pageIds = computed(getPageIds);

    const allSelected = computed(
        () =>
            pageIds.value.length > 0 &&
            pageIds.value.every((id) => selectedIds.has(id)),
    );

    const someSelected = computed(
        () => selectedIds.size > 0 && !allSelected.value,
    );

    const headerCheckboxState = computed(() =>
        allSelected.value ? true : someSelected.value ? 'indeterminate' : false,
    );

    function toggleAll(checked: boolean | 'indeterminate') {
        if (checked === true) {
            pageIds.value.forEach((id) => selectedIds.add(id));
        } else {
            pageIds.value.forEach((id) => selectedIds.delete(id));
        }
    }

    function toggleRow(id: number, checked: boolean | 'indeterminate') {
        if (checked === true) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
    }

    function clearSelection() {
        selectedIds.clear();
    }

    watch(pageIds, () => clearSelection());

    return {
        selectedIds,
        headerCheckboxState,
        toggleAll,
        toggleRow,
        clearSelection,
    };
}
