<script setup>
import { useLanguage } from '@/composables/useLanguage';

// Phone-only button that opens/closes a page's filter form (below md the form is
// collapsed so results show on the first screen; from md up the form is always open).
defineProps({
    open: { type: Boolean, default: false },
    // Number of filters currently applied to the results.
    count: { type: Number, default: 0 },
    controls: { type: String, required: true },
});

defineEmits(['toggle']);

const { __ } = useLanguage();
</script>

<template>
    <div class="mb-4 px-4 sm:px-0 md:hidden">
        <button
            type="button"
            class="flex min-h-11 w-full items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-base font-bold text-gray-800 shadow-xs"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-controls="controls"
            @click="$emit('toggle')"
        >
            <span class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18l-7 8v6l-4 2v-8L3 4z" /></svg>
                {{ __('common_filters') }}
                <span v-if="count" class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-orange-600 px-2 text-xs font-bold text-white">{{ count }}</span>
            </span>
            <svg class="h-5 w-5 text-gray-500 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
        </button>
    </div>
</template>
