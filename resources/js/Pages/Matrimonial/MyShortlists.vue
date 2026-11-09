<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({
    profiles: Object
});

const toggleShortlist = async (profile) => {
    try {
        const response = await axios.post(route('matrimonial-directory.shortlist', profile.id));
        if (response.data.status === 'added') {
            profile.is_shortlisted = true;
        } else if (response.data.status === 'removed') {
            profile.is_shortlisted = false;
        }
    } catch (error) {
        console.error('Error toggling shortlist:', error);
    }
};
</script>

<template>
    <Head title="My Shortlists" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Shortlists</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-6 flex justify-between items-center bg-white p-4 shadow-xs rounded-lg">
                    <h3 class="text-lg font-medium text-gray-700">Profiles you have saved</h3>
                    <Link :href="route('matrimonial-directory.index')" class="text-indigo-600 hover:text-indigo-900 text-sm font-semibold">
                        &larr; Back to Directory
                    </Link>
                </div>

                <!-- Directory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="profile in profiles.data" :key="profile.id" class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-20 h-20 bg-gray-200 rounded-full overflow-hidden border-2 border-indigo-100">
                                    <img v-if="profile.photo_url && !profile.hide_photo" :src="profile.photo_url" class="w-full h-full object-cover" alt="Profile Photo">
                                    <svg v-else class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                      <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ profile.full_name }}</h3>
                                    <p class="text-sm text-gray-500">{{ profile.dob }} • {{ profile.gender }}</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p><span class="font-medium text-gray-900">Education:</span> {{ profile.education || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Occupation:</span> {{ profile.occupation || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Location:</span> {{ profile.current_city || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Marital Status:</span> {{ profile.marital_status }}</p>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-between items-center">
                            <a v-if="profile.biodata_url" :href="profile.biodata_url" target="_blank" rel="noopener" class="text-green-600 hover:text-green-800 font-medium text-sm">View Biodata</a>
                            <span v-else class="text-xs text-gray-400" title="Biodata is shared after an accepted interest">Biodata after accepted interest</span>
                            <button @click="toggleShortlist(profile)" class="flex items-center gap-1 focus:outline-hidden transition-colors duration-200" :class="profile.is_shortlisted ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-red-500'">
                                <svg v-if="profile.is_shortlisted" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span class="text-sm font-medium">{{ profile.is_shortlisted ? 'Saved' : 'Shortlist' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="profiles.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex shadow-xs rounded-md">
                        <template v-for="(link, p) in profiles.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="profiles.data.length === 0" class="text-center py-16 bg-white rounded-lg shadow-xs">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No saved profiles</h3>
                    <p class="mt-1 text-sm text-gray-500">You haven't shortlisted any matrimonial profiles yet.</p>
                    <div class="mt-6">
                        <Link :href="route('matrimonial-directory.index')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Browse Directory
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
