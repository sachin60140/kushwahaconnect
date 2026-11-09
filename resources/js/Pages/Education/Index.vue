<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    resources: Object,
    filters: Object
});

const form = useForm({
    category: props.filters.category || ''
});

const submitSearch = () => {
    form.get(route('education.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Education Support" />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Growth & Learning</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Education Resources & Scholarships</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Scholarships, courses, mentorship, and guidance for community youth.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg mb-6 p-6">
                    <form @submit.prevent="submitSearch" class="flex gap-4 items-end">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Filter by Category</label>
                            <select v-model="form.category" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">All Categories</option>
                                <option value="Scholarship">Scholarship</option>
                                <option value="Course">Course</option>
                                <option value="Mentorship">Mentorship</option>
                                <option value="General">General</option>
                            </select>
                        </div>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden">
                            Filter
                        </button>
                    </form>
                </div>

                <!-- Resources Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="resource in resources.data" :key="resource.id" class="bg-white overflow-hidden shadow-xs sm:rounded-lg flex flex-col">
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full" 
                                      :class="{
                                          'bg-purple-100 text-purple-800': resource.category === 'Scholarship',
                                          'bg-blue-100 text-blue-800': resource.category === 'Course',
                                          'bg-green-100 text-green-800': resource.category === 'Mentorship',
                                          'bg-gray-100 text-gray-800': resource.category === 'General'
                                      }">
                                    {{ resource.category }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ resource.title }}</h3>
                            <p class="text-gray-600 text-sm mb-4 flex-1 whitespace-pre-line">{{ resource.description }}</p>
                            
                            <div class="mt-auto space-y-3">
                                <div v-if="resource.deadline_date" class="flex items-center text-sm text-red-600 font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Deadline: {{ new Date(resource.deadline_date).toLocaleDateString() }}
                                </div>
                                <a v-if="resource.link_url" :href="resource.link_url" target="_blank" rel="noopener noreferrer" class="block w-full text-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    View details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="resources.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex shadow-xs rounded-md">
                        <template v-for="(link, p) in resources.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="resources.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow-xs">
                    <p class="text-gray-500">No resources found matching your criteria.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
