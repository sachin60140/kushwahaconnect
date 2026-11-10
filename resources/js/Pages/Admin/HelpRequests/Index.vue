<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
    publicStatuses: {
        type: Array,
        default: () => ['Approved', 'Assigned', 'In Progress', 'Resolved'],
    },
});

// Only requests visitors can open have a public page to view or share.
const isLive = (req) => req.is_public && props.publicStatuses.includes(req.status);

const form = useForm({});

const updateStatus = (id, newStatus) => {
    // router.patch sends the payload; useForm({}).patch(url, { data }) silently drops it.
    router.patch(route('admin.help-requests.status', id), { status: newStatus }, {
        preserveScroll: true,
        onError: (errors) => alert(errors.status || 'Unable to update status. Please try again.'),
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
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Help Requests Management</h2>
                <Link :href="route('admin.help-requests.create')" class="inline-flex items-center justify-center px-4 py-2 bg-orange-600 text-white text-sm font-bold rounded-lg shadow-xs hover:bg-orange-700 transition-colors">
                    + Create Help Request
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Filter -->
                <div class="mb-6 bg-white p-4 shadow-xs sm:rounded-lg flex gap-4 items-end">
                    <div class="w-64">
                        <label class="block text-sm font-medium text-gray-700">Filter by Status</label>
                        <select v-model="searchForm.status" @change="filterByStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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

                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
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
                                        <div class="mt-1 flex flex-wrap gap-1 text-[11px] font-bold">
                                            <span v-if="req.documents_count" class="rounded-sm bg-blue-50 px-1.5 py-0.5 text-blue-700">📎 {{ req.documents_count }} document{{ req.documents_count > 1 ? 's' : '' }}</span>
                                            <span v-if="req.bank_account_number" class="rounded-sm bg-green-50 px-1.5 py-0.5 text-green-700">Bank details</span>
                                            <span v-if="req.upi_id || req.upi_qr_url" class="rounded-sm bg-green-50 px-1.5 py-0.5 text-green-700">UPI</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-medium">{{ req.name }}</div>
                                        <div class="text-gray-500">{{ req.mobile }}</div>
                                        <div v-if="req.whatsapp" class="text-green-600 text-xs">WA: {{ req.whatsapp }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <select :value="req.status" @change="updateStatus(req.id, $event.target.value)" class="text-sm rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 font-semibold"
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
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <Link :href="route('admin.help-requests.edit', req.id)" class="text-indigo-600 hover:text-indigo-900 font-bold">Edit</Link>
                                        <a v-if="isLive(req)" :href="route('help.show', req.id)" target="_blank" rel="noopener" class="text-green-700 hover:text-green-900 font-bold">View / Share</a>
                                        <button @click="destroy(req.id)" class="text-red-600 hover:text-red-900 bg-red-50 px-2 py-1 rounded-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="requests.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No requests found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div v-if="requests.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex shadow-xs rounded-md">
                                <template v-for="(link, p) in requests.links" :key="p">
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
