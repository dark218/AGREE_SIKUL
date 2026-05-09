import { ref } from 'vue';

export function useCollapsible(initialState = false) {
    const isCollapsed = ref(initialState);

    const toggleCollapse = () => {
        isCollapsed.value = !isCollapsed.value;
    };

    return {
        isCollapsed,
        toggleCollapse,
    };
}
