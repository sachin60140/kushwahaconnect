<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object
});

const form = useForm({});

const updateStatus = (id, newStatus) => {
    form.patch(route('admin.help-requests.status', id), {
        preserveScroll: true,
        data: { status: newStatus }
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this help request?')) {
        form.delete(route('admin.help-requests.destroy', id), {
            preserveScroll: true
        });
    }
};

const searchForm = useForm({
    status: props.filters.status || ''
});

const filterByStatus = () => {
    searchForm.get(route('admin.help-requests.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Manage Help Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Help Requests Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filter -->
                <div class="mb-6 bg-white p-4 shadow-sm sm:rounded-lg flex gap-4 items-end">
                    <div class="w-64">
                        <label class="block text-sm font-medium text-gray-700">Filter by Status</label>
                        <select v-model="searchForm.status" @change="filterByStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">All Statuses</option>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Assigned">Assigned</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Resolved">Resolved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Details</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="req in requests.data" :key="req.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-bold text-indigo-600">{{ req.title }}</div>
                                        <div class="text-xs text-gray-500 mb-1">{{ req.category }} • <span :class="{'text-red-600 font-bold': req.urgency === 'Emergency'}">{{ req.urgency }}</span></div>
                                        <div class="text-xs text-gray-500">Location: {{ req.city }}, {{ req.district }}</div>
                                        <div class="mt-2 text-sm text-gray-600 line-clamp-2 max-w-xs" :title="req.description">{{ req.description }}</div>
                                        <div v-if="req.is_public" class="mt-1 text-xs font-semibold text-green-600">Public Request</div>
                                        <div v-else class="mt-1 text-xs font-semibold text-gray-500">Private Request</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ req.name }}</div>
                                        <div class="text-gray-500">{{ req.mobile }}</div>
                                        <div v-if="req.whatsapp" class="text-green-600 text-xs">WA: {{ req.whatsapp }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <select :value="req.status" @change="updateStatus(req.id, $event.target.value)" class="text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
                                                :class="{
                                                    'bg-yellow-100 text-yellow-800': req.status === 'Pending',
                                                    'bg-green-100 text-green-800': req.status === 'Approved',
                                                    'bg-blue-100 text-blue-800': req.status === 'Assigned',
                                                    'bg-indigo-100 text-indigo-800': req.status === 'In Progress',
                                                    'bg-gray-100 text-gray-800': req.status === 'Resolved',
                                                    'bg-red-100 text-red-800': req.status === 'Rejected',
                                                }">
                                            <option value="Pending">Pending</option>
                                            <option value="Approved">Approved</option>
                                            <option value="Assigned">Assigned</option>
                                            <option value="In Progress">In Progress</option>
                                            <option value="Resolved">Resolved</option>
                                            <option value="Rejected">Rejected</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="destroy(req.id)" class="text-red-600 hover:text-red-900 bg-red-50 px-2 py-1 rounded">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="requests.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No requests found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div v-if="requests.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex shadow-sm rounded-md">
                                <template v-for="(link, p) in requests.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
