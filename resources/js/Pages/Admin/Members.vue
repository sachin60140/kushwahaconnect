<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    members: Object,
    states: Array,
    filters: Object
});

const page = usePage();
const statusMessage = ref('');
const statusError = ref('');
const processingMemberId = ref(null);
const processingStatus = ref('');
const deleteForm = useForm({});

const updateStatus = (id, status) => {
    if (processingMemberId.value) return;

    statusMessage.value = '';
    statusError.value = '';
    processingMemberId.value = id;
    processingStatus.value = status;

    router.patch(route('admin.members.status', id), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            statusMessage.value = status === 'approved'
                ? 'Member approved successfully.'
                : 'Member rejected successfully.';
        },
        onError: (errors) => {
            statusError.value = errors.status || 'Unable to update member status. Please try again.';
        },
        onFinish: () => {
            processingMemberId.value = null;
            processingStatus.value = '';
        }
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this member?')) {
        deleteForm.delete(route('admin.members.destroy', id), {
            preserveScroll: true
        });
    }
};

// Filters
const filterForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
    state_id: props.filters.state_id || '',
    district_id: props.filters.district_id || ''
});

const availableDistricts = computed(() => {
    if (!filterForm.state_id) return [];
    const selectedState = props.states.find(s => s.id == filterForm.state_id);
    return selectedState ? selectedState.districts : [];
});

watch(() => filterForm.state_id, () => {
    filterForm.district_id = '';
});

const applyFilters = () => {
    filterForm.get(route('admin.members'), {
        preserveState: true,
        preserveScroll: true
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    filterForm.state_id = '';
    filterForm.district_id = '';
    applyFilters();
};

// Modal for Viewing Details
const selectedMember = ref(null);
const showModal = ref(false);

const openViewModal = (member) => {
    selectedMember.value = member;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        selectedMember.value = null;
    }, 300);
};
</script>

<template>
    <Head title="Members Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Community Members
                </h2>
                <a :href="route('admin.members.export')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow transition-colors">
                    Export Excel
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <div
                    v-if="page.props.flash?.success || statusMessage"
                    class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-bold text-green-800"
                >
                    {{ statusMessage || page.props.flash.success }}
                </div>

                <div
                    v-if="page.props.flash?.error || statusError"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800"
                >
                    {{ statusError || page.props.flash.error }}
                </div>

                <!-- Filters -->
                <div class="bg-white p-6 shadow-sm sm:rounded-xl border border-gray-100">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                            <input v-model="filterForm.search" type="text" placeholder="Name, Email, Mobile" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                            <select v-model="filterForm.status" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 py-2.5 px-3">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">State</label>
                            <select v-model="filterForm.state_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 py-2.5 px-3">
                                <option value="">All States</option>
                                <option v-for="state in states" :key="state.id" :value="state.id">
                                    {{ state.name_en }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">District</label>
                            <select v-model="filterForm.district_id" :disabled="!filterForm.state_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500 py-2.5 px-3 disabled:bg-gray-100 disabled:text-gray-400">
                                <option value="">All Districts</option>
                                <option v-for="district in availableDistricts" :key="district.id" :value="district.id">
                                    {{ district.name_en }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-5 flex justify-end space-x-3 mt-4">
                            <button type="button" @click="resetFilters" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">Reset</button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">Apply Filters</button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold">Name</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Contact Details</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Location</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Status</th>
                                    <th scope="col" class="px-6 py-4 text-right font-bold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="member in members.data" :key="member.id" class="bg-white border-b hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900">{{ member.full_name }}</div>
                                        <div class="text-xs text-gray-500 font-medium">{{ member.gender }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-700">{{ member.mobile }}</div>
                                        <div class="text-xs text-gray-500">{{ member.email || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="member.state" class="font-medium text-gray-700">{{ member.district?.name_en || 'Unknown' }}, {{ member.state?.name_en || 'Unknown' }}</div>
                                        <div v-else class="text-xs text-gray-400 italic">Not provided</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider shadow-sm"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800 border border-yellow-200': member.status === 'pending',
                                                'bg-green-100 text-green-800 border border-green-200': member.status === 'approved',
                                                'bg-red-100 text-red-800 border border-red-200': member.status === 'rejected'
                                            }">
                                            {{ member.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-4">
                                        <button @click="openViewModal(member)" class="text-indigo-600 hover:text-indigo-900 font-bold transition-colors">View Details</button>
                                        <template v-if="member.status !== 'approved'">
                                            <button
                                                @click="updateStatus(member.id, 'approved')"
                                                :disabled="processingMemberId === member.id"
                                                class="text-green-600 hover:text-green-900 font-bold transition-colors disabled:cursor-not-allowed disabled:text-gray-400"
                                            >
                                                {{ processingMemberId === member.id && processingStatus === 'approved' ? 'Approving...' : 'Approve' }}
                                            </button>
                                        </template>
                                        <template v-if="member.status !== 'rejected'">
                                            <button
                                                @click="updateStatus(member.id, 'rejected')"
                                                :disabled="processingMemberId === member.id"
                                                class="text-red-600 hover:text-red-900 font-bold transition-colors disabled:cursor-not-allowed disabled:text-gray-400"
                                            >
                                                {{ processingMemberId === member.id && processingStatus === 'rejected' ? 'Rejecting...' : 'Reject' }}
                                            </button>
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="members.data.length === 0" class="p-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <p class="mt-4 text-lg font-bold text-gray-900">No members found</p>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100" v-if="members.links && members.links.length > 3">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="members.prev_page_url" :href="members.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</Link>
                        <Link v-if="members.next_page_url" :href="members.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-bold text-gray-900">{{ members.from }}</span> to <span class="font-bold text-gray-900">{{ members.to }}</span> of <span class="font-bold text-gray-900">{{ members.total }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, p) in members.links" :key="p">
                                    <div v-if="link.url === null" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-400" v-html="link.label" />
                                    <Link v-else :href="link.url" :class="[link.active ? 'z-10 bg-green-50 border-green-500 text-green-600 font-bold' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50', 'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors']" v-html="link.label" />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Details Modal -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div v-if="selectedMember" class="p-8">
                <div class="flex justify-between items-start mb-8 border-b border-gray-100 pb-4">
                    <div>
                        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ selectedMember.full_name }}</h2>
                        <p class="text-sm text-gray-500 font-medium mt-1">Community Member Profile</p>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-100 hover:bg-gray-200 p-2 rounded-full">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-8">
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="text-xs font-bold text-orange-600 uppercase tracking-widest mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            Personal Information
                        </h4>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Gender</dt> <dd class="font-bold text-gray-900">{{ selectedMember.gender }}</dd></div>
                            <div class="flex justify-between" v-if="selectedMember.father_husband_name"><dt class="text-gray-500">S/O, W/O</dt> <dd class="font-bold text-gray-900">{{ selectedMember.father_husband_name }}</dd></div>
                            <div class="flex justify-between" v-if="selectedMember.dob"><dt class="text-gray-500">DOB</dt> <dd class="font-bold text-gray-900">{{ selectedMember.dob }}</dd></div>
                            <div class="flex justify-between" v-if="selectedMember.gotra"><dt class="text-gray-500">Gotra</dt> <dd class="font-bold text-gray-900">{{ selectedMember.gotra }}</dd></div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="text-xs font-bold text-green-600 uppercase tracking-widest mb-4 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            Contact Details
                        </h4>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between"><dt class="text-gray-500">Mobile</dt> <dd class="font-bold text-gray-900">{{ selectedMember.mobile }}</dd></div>
                            <div class="flex justify-between" v-if="selectedMember.whatsapp"><dt class="text-gray-500">WhatsApp</dt> <dd class="font-bold text-gray-900">{{ selectedMember.whatsapp }}</dd></div>
                            <div class="flex justify-between" v-if="selectedMember.email"><dt class="text-gray-500">Email</dt> <dd class="font-bold text-gray-900 truncate ml-4">{{ selectedMember.email }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">Location & Address</h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                            <div v-if="selectedMember.state"><dt class="text-gray-500 block mb-1">State</dt> <dd class="font-bold text-gray-900">{{ selectedMember.state?.name_en }}</dd></div>
                            <div v-if="selectedMember.district"><dt class="text-gray-500 block mb-1">District</dt> <dd class="font-bold text-gray-900">{{ selectedMember.district?.name_en }}</dd></div>
                            <div v-if="selectedMember.city_village"><dt class="text-gray-500 block mb-1">City/Village</dt> <dd class="font-bold text-gray-900">{{ selectedMember.city_village }}</dd></div>
                            <div v-if="selectedMember.pin_code"><dt class="text-gray-500 block mb-1">Pincode</dt> <dd class="font-bold text-gray-900">{{ selectedMember.pin_code }}</dd></div>
                            <div class="md:col-span-2 mt-2" v-if="selectedMember.current_address"><dt class="text-gray-500 block mb-1">Full Address</dt> <dd class="font-medium text-gray-900 leading-relaxed">{{ selectedMember.current_address }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2" v-if="selectedMember.education || selectedMember.occupation || selectedMember.skills">
                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-4 border-b border-gray-100 pb-2">Education & Profession</h4>
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-white border border-gray-200 p-4 rounded-xl shadow-sm">
                            <div v-if="selectedMember.education"><dt class="text-gray-500 block mb-1">Education</dt> <dd class="font-bold text-gray-900">{{ selectedMember.education }}</dd></div>
                            <div v-if="selectedMember.occupation"><dt class="text-gray-500 block mb-1">Occupation</dt> <dd class="font-bold text-gray-900">{{ selectedMember.occupation }}</dd></div>
                            <div v-if="selectedMember.company_name"><dt class="text-gray-500 block mb-1">Company</dt> <dd class="font-bold text-gray-900">{{ selectedMember.company_name }}</dd></div>
                            <div class="md:col-span-2 mt-2" v-if="selectedMember.skills"><dt class="text-gray-500 block mb-1">Skills</dt> <dd class="font-medium text-gray-900">{{ selectedMember.skills }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2 pt-6 flex justify-end space-x-3 mt-2 border-t border-gray-100">
                        <button
                            v-if="selectedMember.status !== 'approved'"
                            @click="updateStatus(selectedMember.id, 'approved'); closeModal()"
                            :disabled="processingMemberId === selectedMember.id"
                            class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-sm hover:bg-green-700 transition-colors disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            {{ processingMemberId === selectedMember.id && processingStatus === 'approved' ? 'Approving...' : 'Approve Profile' }}
                        </button>
                        <button
                            v-if="selectedMember.status !== 'rejected'"
                            @click="updateStatus(selectedMember.id, 'rejected'); closeModal()"
                            :disabled="processingMemberId === selectedMember.id"
                            class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg shadow-sm hover:bg-red-700 transition-colors disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            {{ processingMemberId === selectedMember.id && processingStatus === 'rejected' ? 'Rejecting...' : 'Reject Profile' }}
                        </button>
                        <button @click="destroy(selectedMember.id); closeModal()" :disabled="deleteForm.processing" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition-colors disabled:cursor-not-allowed disabled:bg-gray-100">Delete Member</button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
