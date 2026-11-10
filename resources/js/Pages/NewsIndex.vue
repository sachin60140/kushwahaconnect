<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import { displayTimeZone } from '@/utils/displayTime';

defineProps({
    items: Object
});

const selectedItem = ref(null);
const showModal = ref(false);

const openArticle = (item) => {
    selectedItem.value = item;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => selectedItem.value = null, 300);
};

const typeLabel = (type) => ({
    news: 'News',
    event: 'Event',
    success_story: 'Success Story',
    update: 'Update',
}[type] || 'Update');

const typeTone = (type) => ({
    news: 'bg-orange-500',
    event: 'bg-green-500',
    success_story: 'bg-blue-600',
    update: 'bg-slate-700',
}[type] || 'bg-slate-700');
</script>

<template>
    <SeoMeta 
        title="News & Events" 
        description="Stay updated with the latest news, announcements, and upcoming events in the Kushwaha community."
    />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Community Updates</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Community News & Events</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Announcements, success stories, events, and important updates from KushwahaConnect.</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div v-for="item in items.data" :key="item.id" class="bg-white overflow-hidden shadow-lg hover:shadow-xl transition-shadow duration-300 sm:rounded-xl flex flex-col border border-gray-100 group">
                        <div v-if="item.image_url" class="h-56 w-full bg-gray-200 overflow-hidden relative">
                            <img :src="item.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" :alt="item.title">
                            <div class="absolute inset-0 bg-linear-to-t from-black/60 to-transparent"></div>
                            <span class="absolute bottom-4 left-4 px-3 py-1 text-xs font-bold rounded-full text-white shadow-xs" 
                                  :class="typeTone(item.type)">
                                {{ typeLabel(item.type) }}
                            </span>
                        </div>
                        <div v-else class="h-48 w-full bg-linear-to-br from-orange-100 to-green-50 flex items-center justify-center relative">
                            <svg class="h-16 w-16 text-orange-300 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                            </svg>
                            <span class="absolute bottom-4 left-4 px-3 py-1 text-xs font-bold rounded-full text-white shadow-xs" 
                                  :class="typeTone(item.type)">
                                {{ typeLabel(item.type) }}
                            </span>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-3 text-xs text-gray-500 font-medium">
                                <span>{{ new Date(item.created_at).toLocaleDateString() }}</span>
                                <span v-if="item.event_date && item.type === 'event'" class="text-orange-600 flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    {{ new Date(item.event_date).toLocaleDateString(undefined, { timeZone: displayTimeZone() }) }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-orange-600 transition-colors">{{ item.title }}</h3>
                            <p class="text-gray-600 text-sm mb-6 flex-1 line-clamp-3 leading-relaxed">{{ item.content }}</p>
                            <div class="mt-auto border-t border-gray-100 pt-4">
                                <button @click="openArticle(item)" class="text-orange-600 hover:text-orange-800 font-semibold text-sm flex items-center group-hover:underline">
                                    Read Full Story
                                    <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="items.links.length > 3" class="mt-10 flex justify-center">
                    <div class="flex shadow-xs rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in items.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-orange-600 transition-colors duration-200" :class="{ 'bg-orange-50 font-bold text-orange-700 border-b-2 border-orange-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="items.data.length === 0" class="text-center py-16 bg-white rounded-xl shadow-xs border border-gray-100">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                    <p class="text-lg font-medium text-gray-900">No news or events found at the moment.</p>
                    <p class="text-gray-500 mt-1">Check back later for updates from the community.</p>
                </div>
            </div>
        </div>

        <!-- Article Modal -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div class="bg-white rounded-lg overflow-hidden" v-if="selectedItem">
                <div v-if="selectedItem.image_url" class="h-64 w-full bg-gray-200">
                    <img :src="selectedItem.image_url" class="w-full h-full object-cover" :alt="selectedItem.title">
                </div>
                <div class="p-8">
                    <div class="flex items-center justify-between mb-4 text-sm text-gray-500 font-medium border-b pb-4">
                        <span class="bg-gray-100 px-3 py-1 rounded-full">{{ typeLabel(selectedItem.type) }}</span>
                        <span>{{ new Date(selectedItem.created_at).toLocaleDateString() }}</span>
                        <span v-if="selectedItem.event_date && selectedItem.type === 'event'" class="text-orange-600 font-bold flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Date: {{ new Date(selectedItem.event_date).toLocaleDateString(undefined, { timeZone: displayTimeZone() }) }}
                        </span>
                    </div>
                    <h3 class="text-3xl font-extrabold text-gray-900 mb-6 leading-tight">{{ selectedItem.title }}</h3>
                    <div class="prose max-w-none text-gray-700 whitespace-pre-wrap leading-relaxed text-lg">
                        {{ selectedItem.content }}
                    </div>
                    <div class="mt-8 pt-6 border-t border-gray-200 text-right">
                        <button @click="closeModal" class="px-6 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Modal>

    </PublicLayout>
</template>
