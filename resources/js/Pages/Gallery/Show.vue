<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    gallery: Object
});
</script>

<template>
    <Head :title="gallery.title" />

    <PublicLayout>
        <template #header>
            <div class="max-w-4xl">
                <Link :href="route('gallery.index')" class="inline-flex text-sm font-black text-orange-300 hover:text-orange-200">
                    Back to Galleries
                </Link>
                <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ gallery.title }}</h1>
                <p class="mt-4 max-w-3xl text-lg leading-8 text-blue-100">{{ gallery.description }}</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                    <div v-for="image in gallery.images" :key="image.id" class="group relative aspect-w-1 aspect-h-1 overflow-hidden rounded-3xl bg-gray-100 shadow-sm border border-gray-200">
                        <a :href="`/storage/${image.image_path}`" target="_blank" class="block h-full w-full h-48 sm:h-64">
                            <img :src="`/storage/${image.image_path}`" :alt="gallery.title" class="object-cover w-full h-full group-hover:opacity-75 transition-opacity">
                        </a>
                    </div>
                </div>

                <div v-if="gallery.images.length === 0" class="rounded-3xl bg-white py-12 text-center text-gray-500 shadow-sm">
                    No images available in this gallery.
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
