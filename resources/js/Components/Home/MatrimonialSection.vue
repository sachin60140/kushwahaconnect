<script setup>
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();
const points = ['matrimonial_point_1', 'matrimonial_point_2', 'matrimonial_point_3'];
</script>

<template>
    <section class="bg-[#fffaf0] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl items-center gap-10 rounded-3xl bg-white p-6 shadow-xl shadow-blue-950/10 sm:p-8 lg:grid-cols-[0.9fr_1.1fr] lg:p-10">
            <div class="relative min-h-[360px] overflow-hidden rounded-3xl bg-linear-to-br from-blue-950 via-blue-900 to-orange-600 p-6 text-white">
                <div class="absolute -right-12 -top-12 h-48 w-48 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-10 -left-10 h-44 w-44 rounded-full bg-green-400/20 blur-2xl"></div>
                <div class="relative space-y-4">
                    <div class="rounded-3xl bg-white/12 p-5 backdrop-blur-sm">
                        <span class="rounded-full bg-orange-400 px-3 py-1 text-xs font-black text-blue-950">{{ __('verified') }}</span>
                        <div class="mt-5 h-4 w-3/4 rounded-full bg-white/30"></div>
                        <div class="mt-3 h-4 w-1/2 rounded-full bg-white/20"></div>
                    </div>
                    <div class="ml-auto max-w-xs rounded-3xl bg-white p-5 text-blue-950 shadow-xl">
                        <p class="text-sm font-black">{{ __('matrimonial_desc') }}</p>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="h-14 rounded-2xl bg-orange-100"></div>
                            <div class="h-14 rounded-2xl bg-green-100"></div>
                            <div class="h-14 rounded-2xl bg-blue-100"></div>
                        </div>
                    </div>
                    <div class="max-w-sm rounded-3xl bg-green-500/20 p-5 backdrop-blur-sm">
                        <p class="text-sm font-bold text-green-50">{{ __('matrimonial_point_2') }}</p>
                    </div>
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-600">{{ __('menu_matrimonial') }}</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ __('matrimonial_title') }}</h2>
                <p class="mt-5 text-lg leading-8 text-slate-700">{{ __('matrimonial_desc') }}</p>

                <div class="mt-7 space-y-4">
                    <div v-for="point in points" :key="point" class="flex gap-3 rounded-2xl bg-green-50 p-4">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-700 text-sm font-black text-white">✓</span>
                        <p class="text-sm font-bold leading-6 text-slate-700">{{ __(point) }}</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:flex">
                    <Link :href="route('matrimonial')" class="inline-flex min-h-12 items-center justify-center rounded-full bg-orange-500 px-6 text-sm font-black text-white shadow-xs transition hover:bg-orange-600">
                        {{ __('btn_create_profile') }}
                    </Link>
                    <Link :href="route('matrimonial-directory.index')" class="inline-flex min-h-12 items-center justify-center rounded-full border border-blue-950/15 px-6 text-sm font-black text-blue-950 transition hover:border-blue-950 hover:bg-blue-50">
                        {{ __('btn_browse_profiles') }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
