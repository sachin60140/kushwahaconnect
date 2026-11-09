<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    jobs: Object
});

const form = useForm({});

const updateStatus = (id, status) => {
    // router.patch sends the payload; useForm({}).patch(url, { data }) silently drops it.
    router.patch(route('admin.jobs.status', id), { status }, {
        preserveScroll: true,
        onError: (errors) => alert(errors.status || 'Unable to update status. Please try again.'),
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this job posting?')) {
        form.delete(route('admin.jobs.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Manage Job Postings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Job Postings Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title / Company</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="job in jobs.data" :key="job.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ job.user?.name || 'Unknown' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-bold">{{ job.title }}</div>
                                        <div class="text-gray-500">{{ job.company }} &bull; {{ job.location }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': job.status === 'pending',
                                                'bg-green-100 text-green-800': job.status === 'approved',
                                                'bg-red-100 text-red-800': job.status === 'rejected',
                                                'bg-gray-100 text-gray-800': job.status === 'closed'
                                            }">
                                          {{ job.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <button v-if="job.status !== 'approved'" @click="updateStatus(job.id, 'approved')" class="text-green-600 hover:text-green-900">Approve</button>
                                        <button v-if="job.status !== 'rejected'" @click="updateStatus(job.id, 'rejected')" class="text-orange-600 hover:text-orange-900">Reject</button>
                                        <button v-if="job.status !== 'closed'" @click="updateStatus(job.id, 'closed')" class="text-gray-600 hover:text-gray-900">Close</button>
                                        <button @click="destroy(job.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="jobs.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No job postings found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div v-if="jobs.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex shadow-xs rounded-md">
                                <template v-for="(link, p) in jobs.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
