import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { dictionary } from '../dictionary';

export function useLanguage() {
    const page = usePage();
    const currentLocale = computed(() => page.props.locale || 'hi');

    const __ = (key) => {
        return dictionary[currentLocale.value]?.[key] || key;
    };

    return {
        locale: currentLocale,
        __,
    };
}
