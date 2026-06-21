<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    businesses: Object,
    states: Array,
    categories: Array,
    filters: Object
});

const showAdvancedFilters = ref(false);
const districts = ref([]);

const form = useForm({
    search: props.filters.search || '',
    category: props.filters.category || '',
    state_id: props.filters.state_id || '',
    district_id: props.filters.district_id || ''
});

const fetchDistricts = async () => {
    if (form.state_id) {
        const response = await axios.get(`/api/states/${form.state_id}/districts`);
        districts.value = response.data;
    } else {
        districts.value = [];
        form.district_id = '';
    }
};

if (form.state_id) {
    fetchDistricts();
}

watch(() => form.state_id, () => {
    form.district_id = '';
    fetchDistricts();
});

const submitSearch = () => {
    form.get(route('businesses.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <SeoMeta 
        title="Business Directory" 
        description="Discover and support businesses owned by members of the Kushwaha community."
    />

    <PublicLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Business Networking</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Community Business Directory</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">Discover, connect, and support businesses owned by members of our community.</p>
                </div>
                <Link v-if="$page.props.auth.user" :href="route('businesses.create')" class="kc-btn-primary shrink-0">List Your Business</Link>
                <Link v-else :href="route('login')" class="kc-btn-primary shrink-0">Login to List Business</Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Search & Filters -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8 overflow-hidden">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-end gap-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search Businesses</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input type="text" v-model="form.search" @keyup.enter="submitSearch" class="focus:ring-orange-500 focus:border-orange-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2.5" placeholder="Search by name, description, or city...">
                                </div>
                            </div>
                            <div class="w-full md:w-64">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select v-model="form.category" class="mt-1 block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-orange-500 focus:border-orange-500 sm:text-sm rounded-md">
                                    <option value="">All Categories</option>
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                            </div>
                            <div>
                                <button @click="submitSearch" class="w-full md:w-auto flex justify-center items-center px-6 py-2.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                                    Search
                                </button>
                            </div>
                            <div>
                                <button type="button" @click="showAdvancedFilters = !showAdvancedFilters" class="w-full md:w-auto flex justify-center items-center px-4 py-2.5 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                    Filters
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <div v-show="showAdvancedFilters" class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State</label>
                                <select v-model="form.state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm">
                                    <option value="">Any State</option>
                                    <option v-for="state in states" :key="state.id" :value="state.id">{{ state.name_en }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">District</label>
                                <select v-model="form.district_id" :disabled="!form.state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 sm:text-sm disabled:opacity-50">
                                    <option value="">Any District</option>
                                    <option v-for="district in districts" :key="district.id" :value="district.id">{{ district.name_en }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    <div v-for="business in businesses.data" :key="business.id" class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-300 overflow-hidden flex flex-col group">
                        <div v-if="business.cover_image_path" class="h-48 w-full overflow-hidden shrink-0">
                            <img :src="'/storage/' + business.cover_image_path" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Business Cover">
                        </div>
                        <div v-else class="h-48 w-full bg-gradient-to-r from-orange-100 to-orange-50 shrink-0 flex items-center justify-center border-b border-gray-100">
                            <svg class="w-16 h-16 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </div>
                        
                        <div class="p-6 flex-1 flex flex-col relative">
                            <!-- Logo positioned over the cover image line -->
                            <div class="absolute -top-10 left-6 w-16 h-16 rounded-lg bg-white shadow-md border border-gray-100 flex items-center justify-center overflow-hidden shrink-0 p-1">
                                <img v-if="business.logo_path" :src="'/storage/' + business.logo_path" class="w-full h-full object-contain">
                                <span v-else class="text-2xl font-bold text-orange-400">{{ business.business_name.charAt(0) }}</span>
                            </div>
                            
                            <div class="flex justify-end mb-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-800">
                                    {{ business.category }}
                                </span>
                            </div>
                            
                            <h3 class="text-xl font-bold text-gray-900 mb-1 group-hover:text-orange-600 transition-colors line-clamp-1">{{ business.business_name }}</h3>
                            <p class="text-sm text-gray-500 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <span class="line-clamp-1">{{ business.city }}, {{ business.state?.name_en }}</span>
                            </p>
                            
                            <p class="text-gray-600 text-sm mb-6 line-clamp-3 leading-relaxed">{{ business.description }}</p>
                            
                            <div class="space-y-2 border-t border-gray-100 pt-4 mt-auto">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    {{ business.contact_number }}
                                </div>
                                <div v-if="business.email" class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span class="truncate">{{ business.email }}</span>
                                </div>
                                <div v-if="business.website" class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                    <a :href="business.website.startsWith('http') ? business.website : 'https://' + business.website" target="_blank" class="text-orange-600 hover:text-orange-800 hover:underline truncate">
                                        {{ business.website.replace(/^https?:\/\//, '') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="businesses.data.length === 0" class="text-center py-16 bg-white rounded-xl shadow-sm border border-gray-100 mt-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="text-lg font-medium text-gray-900">No businesses found.</p>
                    <p class="text-gray-500 mt-1">Try adjusting your search filters or be the first to list a business!</p>
                </div>

                <!-- Pagination -->
                <div v-if="businesses.links.length > 3" class="mt-10 flex justify-center">
                    <div class="flex shadow-sm rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in businesses.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-orange-600 transition-colors duration-200" :class="{ 'bg-orange-50 font-bold text-orange-700 border-b-2 border-orange-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>
