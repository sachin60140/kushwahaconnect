<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    jobs: Object,
    filters: Object
});

const form = useForm({
    search: props.filters.search || '',
    location: props.filters.location || ''
});

const submitSearch = () => {
    form.get(route('jobs.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Job Board - KushwahaConnect">
        <meta name="description" content="Explore career opportunities, post job openings, and connect with professionals in the Kushwaha community." />
        <meta name="keywords" content="jobs, career, hiring, recruitment, kushwaha" />
    </Head>

    <PublicLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Career Support</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Job Board</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">Find opportunities and share openings for Kushwaha community members.</p>
                </div>
                <Link :href="route('jobs.create')" class="kc-btn-primary shrink-0">
                    Post a Job
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Search -->
                <div class="bg-white overflow-hidden shadow sm:rounded-xl mb-8 p-6">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keywords</label>
                            <input type="text" v-model="form.search" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-base py-3" placeholder="Job title, company...">
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                            <input type="text" v-model="form.location" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-base py-3" placeholder="City, state, or zip...">
                        </div>
                        <div class="md:col-span-1 flex items-end">
                            <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors duration-200">
                                Search Jobs
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Jobs List -->
                <div class="space-y-6">
                    <div v-for="job in jobs.data" :key="job.id" class="bg-white overflow-hidden shadow-md sm:rounded-xl p-6 sm:p-8 border-l-4 border-green-500 hover:shadow-lg transition-shadow duration-300">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 mb-1">{{ job.title }}</h3>
                                <p class="text-sm font-medium text-gray-600">{{ job.company }} &bull; {{ job.location }}</p>
                            </div>
                            <span v-if="job.salary_range" class="px-4 py-1.5 bg-green-50 text-green-700 text-xs font-bold rounded-full border border-green-200">
                                {{ job.salary_range }}
                            </span>
                        </div>
                        <div class="mt-4 text-gray-700 whitespace-pre-line leading-relaxed">
                            {{ job.description }}
                        </div>
                        <div v-if="job.requirements" class="mt-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
                            <h4 class="font-semibold text-gray-900 text-sm mb-2">Requirements:</h4>
                            <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ job.requirements }}</p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-xs text-gray-500 font-medium">Posted {{ new Date(job.created_at).toLocaleDateString() }}</span>
                            <a :href="`mailto:${job.contact_email}?subject=Application for ${job.title}`" class="text-green-600 hover:text-green-800 font-bold text-sm flex items-center gap-1 transition-colors duration-200 bg-green-50 px-4 py-2 rounded-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                Apply via Email
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="jobs.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex shadow-sm rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in jobs.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-green-600 transition-colors duration-200" :class="{ 'bg-green-50 font-bold text-green-700 border-b-2 border-green-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="jobs.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <p class="text-gray-500">No jobs found matching your criteria.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
