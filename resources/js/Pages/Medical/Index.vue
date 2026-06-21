<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    resources: Object,
    filters: Object
});

const form = useForm({
    type: props.filters.type || '',
    district: props.filters.district || ''
});

const submitSearch = () => {
    form.get(route('medical.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Medical & Emergency Support" />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Health Support</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Medical Assistance & Resources</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Blood requests, doctors, hospitals, and urgent medical support resources.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <form @submit.prevent="submitSearch" class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Filter by Resource Type</label>
                            <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Types</option>
                                <option value="blood_request">Blood Request</option>
                                <option value="doctor">Doctor</option>
                                <option value="hospital">Hospital</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none">
                            Filter
                        </button>
                    </form>
                </div>

                <!-- Resources Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-for="resource in resources.data" :key="resource.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4"
                         :class="{
                             'border-red-500': resource.type === 'blood_request',
                             'border-blue-500': resource.type === 'doctor',
                             'border-green-500': resource.type === 'hospital',
                             'border-gray-500': resource.type === 'other'
                         }">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xl font-bold text-gray-900">{{ resource.title }}</h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 uppercase tracking-wide">
                                    {{ resource.type.replace('_', ' ') }}
                                </span>
                            </div>
                            
                            <div v-if="resource.type === 'blood_request' && resource.blood_group" class="mb-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-md text-sm font-bold bg-red-100 text-red-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
                                    Blood Group Required: {{ resource.blood_group }}
                                </span>
                                <span v-if="resource.urgency" class="ml-2 text-sm text-red-600 font-semibold">{{ resource.urgency }}</span>
                            </div>

                            <p class="text-gray-700 text-sm mb-4 whitespace-pre-line">{{ resource.description }}</p>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <h4 class="font-semibold text-gray-900 text-sm">Contact Information:</h4>
                                <p class="text-sm text-gray-600 mt-1 whitespace-pre-line">{{ resource.contact_info }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="resources.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex shadow-sm rounded-md">
                        <template v-for="(link, p) in resources.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-red-500 focus:text-red-500" :class="{ 'bg-white font-bold text-red-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="resources.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <p class="text-gray-500">No resources found matching your criteria.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
