<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const page = usePage();
const { locale, __ } = useLanguage();

const settings = computed(() => page.props.websiteSettings || {});
const announcement = computed(() => {
    const key = locale.value === 'hi' ? 'announcement_hi' : 'announcement_en';
    return settings.value[key] || settings.value.announcement || __('announcement_default');
});
</script>

<template>
    <div v-if="announcement" class="bg-blue-950 text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 px-4 py-2 text-center text-sm sm:flex-row sm:text-left">
            <p class="font-medium" :class="{ 'font-devanagari': locale === 'hi' }">
                {{ announcement }}
            </p>
            <Link
                :href="route('join')"
                class="inline-flex min-h-10 items-center justify-center rounded-full bg-orange-500 px-5 text-sm font-bold text-white shadow-xs transition hover:bg-orange-600"
            >
                {{ __('announcement_cta') }}
            </Link>
        </div>
    </div>
</template>
