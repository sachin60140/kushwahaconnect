<script setup>
import { useLanguage } from '@/composables/useLanguage';

defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

const { __ } = useLanguage();

const formatDate = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatTime = (value) => {
    if (!value) {
        return '';
    }

    return new Date(value).toLocaleTimeString(undefined, {
        hour: 'numeric',
        minute: '2-digit',
    });
};
</script>

<template>
    <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ __('events_title') }}</h2>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ __('events_desc') }}</p>
                </div>
                <a href="/news" class="inline-flex min-h-12 items-center justify-center rounded-full border border-blue-950/15 px-6 text-sm font-black text-blue-950 transition hover:border-blue-950 hover:bg-blue-50">
                    {{ __('menu_news') }}
                </a>
            </div>

            <div v-if="events.length" class="mt-9 grid gap-5 md:grid-cols-3">
                <article
                    v-for="event in events"
                    :key="event.id"
                    class="overflow-hidden rounded-3xl border border-slate-100 bg-[#fffaf0] shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10"
                >
                    <div class="h-48 bg-gradient-to-br from-blue-950 to-green-700">
                        <img v-if="event.image_url" :src="event.image_url" :alt="event.title" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="p-5">
                        <div class="flex flex-wrap gap-2 text-xs font-black">
                            <span class="rounded-full bg-orange-100 px-3 py-1 text-orange-700">{{ formatDate(event.event_date) }}</span>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-green-800">{{ formatTime(event.event_date) }}</span>
                        </div>
                        <h3 class="mt-4 line-clamp-2 text-xl font-black text-blue-950">{{ event.title }}</h3>
                        <p class="mt-2 text-sm font-bold text-slate-600">{{ event.event_location || __('event_location_fallback') }}</p>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ event.content }}</p>
                        <a
                            :href="event.registration_url || '/news'"
                            :target="event.registration_url ? '_blank' : '_self'"
                            :rel="event.registration_url ? 'noopener noreferrer' : null"
                            class="mt-5 inline-flex min-h-11 w-full items-center justify-center rounded-full bg-orange-500 px-5 text-sm font-black text-white transition hover:bg-orange-600"
                        >
                            {{ __('event_register') }}
                        </a>
                    </div>
                </article>
            </div>

            <div v-else class="mt-9 rounded-3xl border border-dashed border-slate-300 bg-[#fffaf0] p-10 text-center">
                <p class="text-base font-bold text-slate-600">{{ __('events_empty') }}</p>
            </div>
        </div>
    </section>
</template>
