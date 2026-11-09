<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    // 'dark' for dark footers (public site), 'light' for light backgrounds (dashboard, auth pages).
    tone: {
        type: String,
        default: 'light',
    },
    // Public pages follow the site language; the English-only admin/auth screens don't.
    translate: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const { __ } = useLanguage();
const info = computed(() => page.props.appVersion || null);

const versionLabel = computed(() => (props.translate ? __('footer_version') : 'Version'));
const updatedLabel = computed(() => (props.translate ? __('footer_last_updated') : 'Last updated'));
</script>

<template>
    <p
        v-if="info"
        class="text-xs"
        :class="tone === 'dark' ? 'text-blue-300' : 'text-slate-400'"
        data-testid="app-version"
    >
        <span>{{ versionLabel }} {{ info.version }}</span>
        <span v-if="info.build"> ({{ info.build }})</span>
        <template v-if="info.updated_at_display">
            <span aria-hidden="true"> · </span>
            <span>{{ updatedLabel }}: <time :datetime="info.updated_at">{{ info.updated_at_display }}</time></span>
        </template>
    </p>
</template>
