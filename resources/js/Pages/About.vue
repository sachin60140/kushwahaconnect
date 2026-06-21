<script setup>
import { Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();
</script>

<template>
    <SeoMeta
        title="About KushwahaConnect"
        description="Learn more about KushwahaConnect's mission to unite, empower, and support the Kushwaha community."
    />

    <PublicLayout>
        <section class="kc-hero">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(249,115,22,0.25),_transparent_34%),radial-gradient(circle_at_bottom_right,_rgba(21,128,61,0.22),_transparent_34%)]"></div>
            <div class="kc-hero-inner text-center">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Kushwaha Samaj</p>
                <h1 class="mx-auto mt-4 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl">
                    {{ __('about_title') }}
                </h1>
                <p class="mx-auto mt-5 max-w-3xl text-lg leading-8 text-blue-100">
                    {{ __('hero_tagline') }}
                </p>
            </div>
        </section>

        <section class="kc-page">
            <div class="kc-container grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
                <div class="kc-surface p-6 sm:p-8">
                    <p class="text-lg leading-8 text-slate-700">{{ __('about_p1') }}</p>
                    <p class="mt-5 text-lg leading-8 text-slate-700">{{ __('about_p2') }}</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <Link :href="route('join')" class="kc-btn-primary">{{ __('btn_join') }}</Link>
                        <Link href="/contact" class="kc-btn-secondary">{{ __('menu_contact') }}</Link>
                    </div>
                </div>

                <div class="grid gap-5">
                    <div class="kc-panel bg-orange-50 p-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-orange-500 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <h2 class="mt-5 text-2xl font-black text-orange-700">{{ __('mission_title') }}</h2>
                        <p class="mt-3 leading-7 text-slate-700">{{ __('mission_text') }}</p>
                    </div>
                    <div class="kc-panel bg-green-50 p-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-700 text-white">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M2.5 12C3.7 7.9 7.5 5 12 5s8.3 2.9 9.5 7c-1.2 4.1-5 7-9.5 7s-8.3-2.9-9.5-7Z" /></svg>
                        </div>
                        <h2 class="mt-5 text-2xl font-black text-green-800">{{ __('vision_title') }}</h2>
                        <p class="mt-3 leading-7 text-slate-700">{{ __('vision_text') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
