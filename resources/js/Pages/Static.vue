<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    title: String,
    content: String,
});
</script>

<template>
    <Head :title="`${title} - KushwahaConnect`" />

    <PublicLayout>
        <section class="kc-hero">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.24),transparent_34%)]"></div>
            <div class="kc-hero-inner text-center">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">KushwahaConnect</p>
                <h1 class="mx-auto mt-4 max-w-4xl text-4xl font-black tracking-tight text-white sm:text-5xl">
                    {{ title }}
                </h1>
            </div>
        </section>

        <section class="kc-page">
            <div class="mx-auto max-w-4xl">
                <article class="kc-legal kc-surface p-6 text-lg leading-8 text-slate-700 sm:p-8" v-html="content"></article>
            </div>
        </section>
    </PublicLayout>
</template>
