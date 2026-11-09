<script setup>
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();

const categories = [
    { label: 'help_education', color: 'bg-green-100 text-green-800' },
    { label: 'help_job', color: 'bg-blue-100 text-blue-950' },
    { label: 'help_medical', color: 'bg-red-100 text-red-700' },
    { label: 'help_emergency', color: 'bg-orange-100 text-orange-700' },
    { label: 'help_business', color: 'bg-emerald-100 text-emerald-800' },
    { label: 'help_schemes', color: 'bg-indigo-100 text-indigo-800' },
];
</script>

<template>
    <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-3xl bg-green-900 p-6 text-white shadow-2xl shadow-green-950/15 sm:p-8 lg:p-10">
            <div class="grid gap-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-green-200">{{ __('menu_help') }}</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight sm:text-4xl">{{ __('help_title') }}</h2>
                    <p class="mt-4 text-lg leading-8 text-green-50">{{ __('help_desc') }}</p>
                    <div class="mt-8 grid gap-3 sm:flex">
                        <Link :href="route('help.create')" class="inline-flex min-h-12 items-center justify-center rounded-full bg-orange-500 px-6 text-sm font-black text-white shadow-xs transition hover:bg-orange-600">
                            {{ __('btn_request_help') }}
                        </Link>
                        <Link :href="route('volunteer.create')" class="inline-flex min-h-12 items-center justify-center rounded-full bg-white px-6 text-sm font-black text-green-900 transition hover:bg-green-50">
                            {{ __('btn_become_volunteer') }}
                        </Link>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="category in categories"
                        :key="category.label"
                        class="rounded-2xl bg-white p-5 shadow-xs transition hover:-translate-y-1"
                    >
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="category.color">
                            {{ __(category.label) }}
                        </span>
                        <div class="mt-5 h-3 w-4/5 rounded-full bg-slate-100"></div>
                        <div class="mt-2 h-3 w-2/3 rounded-full bg-slate-100"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
