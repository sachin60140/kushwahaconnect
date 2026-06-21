<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    galleries: Array
});
</script>

<template>
    <Head title="Photo Gallery" />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Community Memories</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Community Photo Gallery</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Memories and events from the Kushwaha community.</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <Link v-for="gallery in galleries" :key="gallery.id" :href="route('gallery.show', gallery.id)" class="group block overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10">
                        <div class="aspect-w-16 aspect-h-10 bg-gray-200 relative overflow-hidden h-64">
                            <img v-if="gallery.thumbnail" :src="`/storage/${gallery.thumbnail}`" :alt="gallery.title" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="absolute inset-0 bg-black bg-opacity-20 group-hover:bg-opacity-10 transition-opacity"></div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-black text-blue-950 group-hover:text-orange-600 transition-colors">{{ gallery.title }}</h3>
                            <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ gallery.description }}</p>
                            <div class="mt-4 flex items-center text-sm font-medium text-gray-500">
                                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                {{ gallery.images.length }} Photos
                            </div>
                        </div>
                    </Link>
                </div>

                <div v-if="galleries.length === 0" class="text-center py-20 bg-white rounded-3xl shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No galleries</h3>
                    <p class="mt-1 text-sm text-gray-500">Check back later for photos from our community events.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
