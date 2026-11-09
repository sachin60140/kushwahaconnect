<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    businesses: Object,
    filters: Object
});

const form = useForm({
    search: props.filters.search || '',
    status: props.filters.status || ''
});

const submitSearch = () => {
    form.get(route('admin.business.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const updateForm = useForm({
    status: ''
});

const updateStatus = (id, newStatus) => {
    updateForm.status = newStatus;
    updateForm.patch(route('admin.business.update-status', id), {
        preserveScroll: true
    });
};

const deleteForm = useForm({});

const deleteBusiness = (id) => {
    if (confirm('Are you sure you want to delete this business listing?')) {
        deleteForm.delete(route('admin.business.destroy', id), {
            preserveScroll: true
        });
    }
};

// Details Modal
const selectedBusiness = ref(null);
const showModal = ref(false);

const viewDetails = (business) => {
    selectedBusiness.value = business;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => selectedBusiness.value = null, 300);
};

</script>

<template>
    <Head title="Manage Business Listings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Manage Business Listings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow-xs mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between border border-gray-200">
                    <div class="flex-1 w-full sm:w-auto">
                        <input type="text" v-model="form.search" @keyup.enter="submitSearch" placeholder="Search by Business or User Name..." class="w-full sm:max-w-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select v-model="form.status" @change="submitSearch" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs w-full sm:w-auto">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button @click="submitSearch" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 transition">Filter</button>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg border border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Details</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="business in businesses.data" :key="business.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="shrink-0 h-10 w-10">
                                                <img v-if="business.logo_path" class="h-10 w-10 rounded-full object-cover" :src="'/storage/' + business.logo_path" alt="">
                                                <div v-else class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-500">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ business.business_name }}</div>
                                                <div class="text-sm text-gray-500">{{ business.category }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ business.user.name }}</div>
                                        <div class="text-sm text-gray-500">{{ business.user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ business.city }}</div>
                                        <div class="text-sm text-gray-500">{{ business.state?.name_en }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': business.status === 'pending',
                                                'bg-green-100 text-green-800': business.status === 'approved',
                                                'bg-red-100 text-red-800': business.status === 'rejected',
                                            }">
                                            {{ business.status.charAt(0).toUpperCase() + business.status.slice(1) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="viewDetails(business)" class="text-indigo-600 hover:text-indigo-900 mr-3">View</button>
                                        <button v-if="business.status !== 'approved'" @click="updateStatus(business.id, 'approved')" class="text-green-600 hover:text-green-900 mr-3">Approve</button>
                                        <button v-if="business.status !== 'rejected'" @click="updateStatus(business.id, 'rejected')" class="text-orange-600 hover:text-orange-900 mr-3">Reject</button>
                                        <button @click="deleteBusiness(business.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="businesses.data.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        No business listings found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div v-if="businesses.links.length > 3" class="mt-6">
                    <div class="flex shadow-xs rounded-md">
                        <template v-for="(link, p) in businesses.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm bg-white" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500 transition" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white': !link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <!-- Details Modal -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div class="p-6" v-if="selectedBusiness">
                <div class="flex items-center justify-between mb-6 border-b pb-4">
                    <h3 class="text-2xl font-bold text-gray-900">Business Details</h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-6">
                        <div v-if="selectedBusiness.logo_path" class="w-24 h-24 rounded-lg border border-gray-200 overflow-hidden shrink-0">
                            <img :src="'/storage/' + selectedBusiness.logo_path" class="w-full h-full object-cover">
                        </div>
                        <div>
                            <h4 class="text-xl font-bold text-gray-900">{{ selectedBusiness.business_name }}</h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mt-1">{{ selectedBusiness.category }}</span>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-md text-sm text-gray-700 whitespace-pre-wrap">
                        <span class="font-bold text-gray-900 block mb-1">Description:</span>
                        {{ selectedBusiness.description }}
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Location:</span>
                            <p>{{ selectedBusiness.address }}</p>
                            <p>{{ selectedBusiness.city }}, {{ selectedBusiness.district?.name_en }}</p>
                            <p>{{ selectedBusiness.state?.name_en }} - {{ selectedBusiness.pincode }}</p>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Contact Details:</span>
                            <p>Phone: {{ selectedBusiness.contact_number }}</p>
                            <p v-if="selectedBusiness.email">Email: {{ selectedBusiness.email }}</p>
                            <p v-if="selectedBusiness.website">Website: {{ selectedBusiness.website }}</p>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Submitted By:</span>
                            <p>{{ selectedBusiness.user.name }} (User ID: {{ selectedBusiness.user_id }})</p>
                        </div>
                        <div>
                            <span class="font-bold text-gray-900 block mb-1">Current Status:</span>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-yellow-100 text-yellow-800': selectedBusiness.status === 'pending',
                                    'bg-green-100 text-green-800': selectedBusiness.status === 'approved',
                                    'bg-red-100 text-red-800': selectedBusiness.status === 'rejected',
                                }">
                                {{ selectedBusiness.status.charAt(0).toUpperCase() + selectedBusiness.status.slice(1) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t pt-4 flex justify-end gap-3">
                    <button v-if="selectedBusiness.status !== 'approved'" @click="updateStatus(selectedBusiness.id, 'approved'); closeModal()" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm font-medium">Approve</button>
                    <button v-if="selectedBusiness.status !== 'rejected'" @click="updateStatus(selectedBusiness.id, 'rejected'); closeModal()" class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 text-sm font-medium">Reject</button>
                    <button @click="closeModal" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 text-sm font-medium">Close</button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>
