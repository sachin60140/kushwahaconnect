<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    volunteers: Object,
    filters: Object
});

const form = useForm({});

const updateStatus = (id, newStatus) => {
    // router.patch sends the payload; useForm({}).patch(url, { data }) silently drops it.
    router.patch(route('admin.volunteers.status', id), { status: newStatus }, {
        preserveScroll: true,
        onError: (errors) => alert(errors.status || 'Unable to update status. Please try again.'),
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this volunteer?')) {
        form.delete(route('admin.volunteers.destroy', id), {
            preserveScroll: true
        });
    }
};

const searchForm = useForm({
    status: props.filters.status || ''
});

const filterByStatus = () => {
    searchForm.get(route('admin.volunteers.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Manage Volunteers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Volunteer Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filter -->
                <div class="mb-6 bg-white p-4 shadow-xs sm:rounded-lg flex gap-4 items-end">
                    <div class="w-64">
                        <label class="block text-sm font-medium text-gray-700">Filter by Status</label>
                        <select v-model="searchForm.status" @change="filterByStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-green-500 sm:text-sm">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volunteer Info</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skills / Availability</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="volunteer in volunteers.data" :key="volunteer.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-bold">{{ volunteer.name }}</div>
                                        <div class="text-xs text-gray-500">Location: {{ volunteer.city }}, {{ volunteer.district }}, {{ volunteer.state }}</div>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            <span v-for="area in volunteer.help_areas" :key="area" class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">
                                                {{ area.replace('_', ' ') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ volunteer.mobile }}</div>
                                        <div v-if="volunteer.whatsapp" class="text-green-600 text-xs">WA: {{ volunteer.whatsapp }}</div>
                                        <div v-if="volunteer.email" class="text-gray-500 text-xs">{{ volunteer.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium text-gray-800">{{ volunteer.skills || 'N/A' }}</div>
                                        <div class="text-gray-500 text-xs">{{ volunteer.available_time || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <select :value="volunteer.status" @change="updateStatus(volunteer.id, $event.target.value)" class="text-sm rounded-md border-gray-300 shadow-xs focus:border-green-500 focus:ring-green-500 font-semibold"
                                                :class="{
                                                    'bg-yellow-100 text-yellow-800': volunteer.status === 'Pending',
                                                    'bg-green-100 text-green-800': volunteer.status === 'Approved',
                                                    'bg-red-100 text-red-800': volunteer.status === 'Rejected',
                                                }">
                                            <option value="Pending">Pending</option>
                                            <option value="Approved">Approved</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="destroy(volunteer.id)" class="text-red-600 hover:text-red-900 bg-red-50 px-2 py-1 rounded-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="volunteers.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No volunteers found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div v-if="volunteers.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex shadow-xs rounded-md">
                                <template v-for="(link, p) in volunteers.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-green-500 focus:text-green-500" :class="{ 'bg-white font-bold text-green-600': link.active }" :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
