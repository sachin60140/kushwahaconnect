<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

// Shows the session flash messages (success / status / error) that controllers
// return with redirects. Used by the layouts so every page confirms actions.
const page = usePage();
const { __ } = useLanguage();

const success = computed(() => page.props.flash?.success || page.props.flash?.status || '');
const error = computed(() => page.props.flash?.error || '');

const dismissed = ref(false);

// A new message (from the next request) should be shown again.
watch([success, error], () => {
    dismissed.value = false;
});
</script>

<template>
    <div v-if="!dismissed && (success || error)" class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8" role="status" aria-live="polite">
        <div
            v-if="error"
            class="flex items-start justify-between gap-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800"
        >
            <span>{{ __(error) }}</span>
            <button type="button" class="shrink-0 text-red-500 hover:text-red-700" aria-label="Dismiss" @click="dismissed = true">&times;</button>
        </div>
        <div
            v-else
            class="flex items-start justify-between gap-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800"
        >
            <span>{{ __(success) }}</span>
            <button type="button" class="shrink-0 text-green-600 hover:text-green-800" aria-label="Dismiss" @click="dismissed = true">&times;</button>
        </div>
    </div>
</template>
