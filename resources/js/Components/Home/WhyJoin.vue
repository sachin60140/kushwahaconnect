<script setup>
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();

const cards = [
    { title: 'why_education_title', desc: 'why_education_desc', accent: 'from-orange-300 to-amber-500', icon: 'M12 14 3 9l9-5 9 5-9 5Zm0 0v7M6 11.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5' },
    { title: 'why_business_title', desc: 'why_business_desc', accent: 'from-emerald-300 to-green-500', icon: 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1m-1 4h1m-1 4h1m6-4h1m-1 4h1' },
    { title: 'why_connections_title', desc: 'why_connections_desc', accent: 'from-blue-200 to-cyan-400', icon: 'M17 20h5v-2a4 4 0 0 0-7.6-1.7M9 20H4v-2a4 4 0 0 1 7.6-1.7M12 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm8 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z' },
    { title: 'why_help_title', desc: 'why_help_desc', accent: 'from-lime-200 to-emerald-400', icon: 'M12 8v8m-4-4h8m5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
    { title: 'why_matrimonial_title', desc: 'why_matrimonial_desc', accent: 'from-rose-200 to-orange-400', icon: 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z' },
    { title: 'why_events_title', desc: 'why_events_desc', accent: 'from-sky-200 to-blue-400', icon: 'M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z' },
];
</script>

<template>
    <section class="bg-[#fffaf0] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="relative overflow-hidden rounded-4xl bg-linear-to-br from-blue-950 via-cyan-950 to-green-900 px-5 py-8 shadow-2xl shadow-blue-950/20 sm:rounded-[2.5rem] sm:px-8 sm:py-10 lg:px-12 lg:py-12">
                <div class="pointer-events-none absolute -left-24 -top-24 h-64 w-64 rounded-full bg-orange-300/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-28 right-0 h-72 w-72 rounded-full bg-green-300/20 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-white/40 to-transparent"></div>

                <div class="relative mx-auto max-w-3xl text-center">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">KushwahaConnect</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('why_title') }}</h2>
                    <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('why_subtitle') }}</p>
                </div>

                <div class="relative mt-10 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(card, index) in cards"
                        :key="card.title"
                        class="group relative min-h-64 overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/12 p-6 shadow-lg shadow-blue-950/10 backdrop-blur-sm transition duration-300 hover:-translate-y-1 hover:border-orange-300/70 hover:bg-white/18 hover:shadow-2xl hover:shadow-blue-950/20"
                        :class="index === 0 || index === 2 ? 'xl:min-h-72' : ''"
                    >
                        <div class="absolute inset-x-6 bottom-6 h-20 rounded-3xl bg-white/12 transition group-hover:bg-white/18"></div>
                        <div class="absolute -right-10 -top-10 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>

                        <div class="relative flex h-full flex-col">
                            <div class="flex items-start justify-between gap-4">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br text-blue-950 shadow-xs" :class="card.accent">
                                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path :d="card.icon" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                                    </svg>
                                </span>
                                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black text-blue-100">
                                    0{{ index + 1 }}
                                </span>
                            </div>

                            <h3 class="mt-6 text-2xl font-black leading-tight text-white">{{ __(card.title) }}</h3>
                            <p class="mt-3 max-w-sm text-sm font-medium leading-6 text-blue-100">{{ __(card.desc) }}</p>

                            <div class="mt-auto pt-7">
                                <span class="inline-flex h-2 w-24 rounded-full bg-linear-to-r" :class="card.accent"></span>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
