<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    profiles: Object,
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const statusMessage = ref('');
const statusError = ref('');
const processingId = ref(null);
const processingStatus = ref('');
const deleteForm = useForm({});

const updateStatus = (id, status) => {
    if (processingId.value) return;

    statusMessage.value = '';
    statusError.value = '';
    processingId.value = id;
    processingStatus.value = status;

    // router.patch sends the payload; useForm({}).patch(url, { data }) silently drops it.
    router.patch(route('admin.matrimonial.status', id), { status }, {
        preserveScroll: true,
        onSuccess: () => {
            statusMessage.value = status === 'approved'
                ? 'Matrimonial profile approved successfully.'
                : 'Matrimonial profile rejected successfully.';

            if (selectedProfile.value?.id === id) {
                selectedProfile.value = props.profiles.data.find((profile) => profile.id === id) || null;
            }
        },
        onError: (errors) => {
            statusError.value = errors.status || 'Unable to update profile status. Please try again.';
        },
        onFinish: () => {
            processingId.value = null;
            processingStatus.value = '';
        },
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this profile?')) {
        deleteForm.delete(route('admin.matrimonial.destroy', id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

// Filters
const filterForm = useForm({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const applyFilters = () => {
    filterForm.get(route('admin.matrimonial'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    applyFilters();
};

// Details modal
const selectedProfile = ref(null);
const showModal = ref(false);

const openViewModal = (profile) => {
    selectedProfile.value = profile;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        selectedProfile.value = null;
    }, 300);
};

const ageFromDob = (dob) => {
    if (!dob) return null;
    const birthDate = new Date(dob);
    if (Number.isNaN(birthDate.getTime())) return null;
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    return age;
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
};

const statusClass = (status) => ({
    'bg-yellow-100 text-yellow-800 border border-yellow-200': status === 'pending',
    'bg-green-100 text-green-800 border border-green-200': status === 'approved',
    'bg-red-100 text-red-800 border border-red-200': status === 'rejected',
});
</script>

<template>
    <Head title="Matrimonial Management" />

    <AuthenticatedLayout :show-flash="false">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Matrimonial Profiles
                </h2>
                <a :href="route('admin.matrimonial.export')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-sm shadow-sm transition-colors">
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
                <div class="bg-white p-6 shadow-xs sm:rounded-xl border border-gray-100">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Search</label>
                            <input v-model="filterForm.search" type="text" placeholder="Name, Mobile, Email, City" class="w-full border-gray-300 rounded-lg shadow-xs focus:ring-pink-500 focus:border-pink-500 py-2.5 px-3">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                            <select v-model="filterForm.status" class="w-full border-gray-300 rounded-lg shadow-xs focus:ring-pink-500 focus:border-pink-500 py-2.5 px-3">
                                <option value="">All</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" @click="resetFilters" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">Reset</button>
                            <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-xs">Apply</button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-xl border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-4 font-bold">Profile</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Details</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Contact</th>
                                    <th scope="col" class="px-6 py-4 font-bold">Status</th>
                                    <th scope="col" class="px-6 py-4 text-right font-bold">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="profile in profiles.data" :key="profile.id" class="bg-white border-b hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <img v-if="profile.photo_url" :src="profile.photo_url" :alt="profile.full_name" class="h-12 w-12 rounded-full object-cover border border-gray-200">
                                            <div v-else class="h-12 w-12 rounded-full bg-pink-100 text-pink-700 flex items-center justify-center font-bold uppercase">
                                                {{ profile.full_name?.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900">{{ profile.full_name }}</div>
                                                <div class="text-xs text-gray-500 font-medium">Profile for: {{ profile.profile_for }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-700">
                                            {{ profile.gender }}<span v-if="ageFromDob(profile.dob) !== null">, {{ ageFromDob(profile.dob) }} yrs</span>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ profile.marital_status }}<span v-if="profile.current_city"> · {{ profile.current_city }}</span></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-700">{{ profile.mobile }}</div>
                                        <div class="text-xs text-gray-500">{{ profile.email || 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider shadow-xs" :class="statusClass(profile.status)">
                                            {{ profile.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-4 whitespace-nowrap">
                                        <button @click="openViewModal(profile)" class="text-indigo-600 hover:text-indigo-900 font-bold transition-colors">View Details</button>
                                        <button
                                            v-if="profile.status !== 'approved'"
                                            @click="updateStatus(profile.id, 'approved')"
                                            :disabled="processingId === profile.id"
                                            class="text-green-600 hover:text-green-900 font-bold transition-colors disabled:cursor-not-allowed disabled:text-gray-400"
                                        >
                                            {{ processingId === profile.id && processingStatus === 'approved' ? 'Approving...' : 'Approve' }}
                                        </button>
                                        <button
                                            v-if="profile.status !== 'rejected'"
                                            @click="updateStatus(profile.id, 'rejected')"
                                            :disabled="processingId === profile.id"
                                            class="text-red-600 hover:text-red-900 font-bold transition-colors disabled:cursor-not-allowed disabled:text-gray-400"
                                        >
                                            {{ processingId === profile.id && processingStatus === 'rejected' ? 'Rejecting...' : 'Reject' }}
                                        </button>
                                        <button @click="destroy(profile.id)" :disabled="deleteForm.processing" class="text-gray-600 hover:text-gray-900 font-bold transition-colors disabled:cursor-not-allowed disabled:text-gray-400">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="profiles.data.length === 0" class="p-12 text-center text-gray-500">
                            <p class="text-lg font-bold text-gray-900">No profiles found</p>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your filters.</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-between mt-4 bg-white p-4 rounded-xl shadow-xs border border-gray-100" v-if="profiles.links && profiles.links.length > 3">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link v-if="profiles.prev_page_url" :href="profiles.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</Link>
                        <Link v-if="profiles.next_page_url" :href="profiles.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-bold text-gray-900">{{ profiles.from }}</span> to <span class="font-bold text-gray-900">{{ profiles.to }}</span> of <span class="font-bold text-gray-900">{{ profiles.total }}</span> results
                        </p>
                        <nav class="relative z-0 inline-flex rounded-md shadow-xs -space-x-px" aria-label="Pagination">
                            <template v-for="(link, p) in profiles.links" :key="p">
                                <div v-if="link.url === null" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-50 text-sm font-medium text-gray-400" v-html="link.label" />
                                <Link v-else :href="link.url" :class="[link.active ? 'z-10 bg-pink-50 border-pink-500 text-pink-600 font-bold' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50', 'relative inline-flex items-center px-4 py-2 border text-sm font-medium transition-colors']" v-html="link.label" />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Details Modal -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div v-if="selectedProfile" class="p-6 sm:p-8">
                <div class="flex justify-between items-start gap-4 mb-6 border-b border-gray-100 pb-4">
                    <div class="flex items-center gap-4">
                        <a v-if="selectedProfile.photo_url" :href="selectedProfile.photo_url" target="_blank" rel="noopener">
                            <img :src="selectedProfile.photo_url" :alt="selectedProfile.full_name" class="h-20 w-20 rounded-xl object-cover border border-gray-200">
                        </a>
                        <div v-else class="h-20 w-20 rounded-xl bg-pink-100 text-pink-700 flex items-center justify-center text-2xl font-bold uppercase">
                            {{ selectedProfile.full_name?.charAt(0) }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-extrabold text-gray-900 tracking-tight">{{ selectedProfile.full_name }}</h2>
                            <p class="text-sm text-gray-500 font-medium mt-1">
                                Matrimonial Profile · Submitted {{ formatDate(selectedProfile.created_at) }}
                            </p>
                            <span class="inline-block mt-2 px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider" :class="statusClass(selectedProfile.status)">
                                {{ selectedProfile.status }}
                            </span>
                        </div>
                    </div>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors bg-gray-100 hover:bg-gray-200 p-2 rounded-full" aria-label="Close">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="text-xs font-bold text-orange-600 uppercase tracking-widest mb-4">Personal Information</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Profile For</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.profile_for }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Gender</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.gender }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Date of Birth</dt> <dd class="font-bold text-gray-900 text-right">{{ formatDate(selectedProfile.dob) }}<span v-if="ageFromDob(selectedProfile.dob) !== null"> ({{ ageFromDob(selectedProfile.dob) }} yrs)</span></dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Marital Status</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.marital_status }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.height"><dt class="text-gray-500">Height</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.height }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.manglik"><dt class="text-gray-500">Manglik</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.manglik }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.gotra"><dt class="text-gray-500">Gotra</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.gotra }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.mother_tongue"><dt class="text-gray-500">Mother Tongue</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.mother_tongue }}</dd></div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                        <h4 class="text-xs font-bold text-green-600 uppercase tracking-widest mb-4">Contact &amp; Account</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Mobile</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.mobile }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.whatsapp"><dt class="text-gray-500">WhatsApp</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.whatsapp }}</dd></div>
                            <div class="flex justify-between gap-4" v-if="selectedProfile.email"><dt class="text-gray-500">Email</dt> <dd class="font-bold text-gray-900 text-right break-all">{{ selectedProfile.email }}</dd></div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Linked Account</dt>
                                <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.user ? selectedProfile.user.name : 'Guest submission' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">ID Proof</dt>
                                <dd class="font-bold text-right">
                                    <a v-if="selectedProfile.id_proof_url" :href="selectedProfile.id_proof_url" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">View document</a>
                                    <span v-else class="text-gray-400">Not uploaded</span>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">Photo hidden</dt> <dd class="font-bold text-gray-900 text-right">{{ selectedProfile.hide_photo ? 'Yes' : 'No' }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2" v-if="selectedProfile.education || selectedProfile.college || selectedProfile.occupation || selectedProfile.company || selectedProfile.annual_income || selectedProfile.work_location">
                        <h4 class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Education &amp; Career</h4>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white border border-gray-200 p-4 rounded-xl">
                            <div v-if="selectedProfile.education"><dt class="text-gray-500 mb-1">Education</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.education }}</dd></div>
                            <div v-if="selectedProfile.college"><dt class="text-gray-500 mb-1">College</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.college }}</dd></div>
                            <div v-if="selectedProfile.occupation"><dt class="text-gray-500 mb-1">Occupation</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.occupation }}</dd></div>
                            <div v-if="selectedProfile.company"><dt class="text-gray-500 mb-1">Company</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.company }}</dd></div>
                            <div v-if="selectedProfile.annual_income"><dt class="text-gray-500 mb-1">Annual Income</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.annual_income }}</dd></div>
                            <div v-if="selectedProfile.work_location"><dt class="text-gray-500 mb-1">Work Location</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.work_location }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2">
                        <h4 class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Family &amp; Location</h4>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white border border-gray-200 p-4 rounded-xl">
                            <div v-if="selectedProfile.father_name"><dt class="text-gray-500 mb-1">Father</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.father_name }}<span v-if="selectedProfile.father_occupation" class="font-medium text-gray-600"> ({{ selectedProfile.father_occupation }})</span></dd></div>
                            <div v-if="selectedProfile.mother_name"><dt class="text-gray-500 mb-1">Mother</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.mother_name }}<span v-if="selectedProfile.mother_occupation" class="font-medium text-gray-600"> ({{ selectedProfile.mother_occupation }})</span></dd></div>
                            <div><dt class="text-gray-500 mb-1">Siblings</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.brothers ?? 0 }} brother(s), {{ selectedProfile.sisters ?? 0 }} sister(s)</dd></div>
                            <div v-if="selectedProfile.family_type"><dt class="text-gray-500 mb-1">Family Type</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.family_type }}</dd></div>
                            <div v-if="selectedProfile.current_city"><dt class="text-gray-500 mb-1">Current City</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.current_city }}</dd></div>
                            <div v-if="selectedProfile.native_place"><dt class="text-gray-500 mb-1">Native Place</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.native_place }}</dd></div>
                            <div v-if="selectedProfile.family_location"><dt class="text-gray-500 mb-1">Family Location</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.family_location }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2" v-if="selectedProfile.pref_age_range || selectedProfile.pref_height || selectedProfile.pref_education || selectedProfile.pref_location || selectedProfile.pref_profession || selectedProfile.other_expectations">
                        <h4 class="text-xs font-bold text-pink-600 uppercase tracking-widest mb-3 border-b border-gray-100 pb-2">Partner Preferences</h4>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white border border-gray-200 p-4 rounded-xl">
                            <div v-if="selectedProfile.pref_age_range"><dt class="text-gray-500 mb-1">Age Range</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.pref_age_range }}</dd></div>
                            <div v-if="selectedProfile.pref_height"><dt class="text-gray-500 mb-1">Height</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.pref_height }}</dd></div>
                            <div v-if="selectedProfile.pref_education"><dt class="text-gray-500 mb-1">Education</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.pref_education }}</dd></div>
                            <div v-if="selectedProfile.pref_profession"><dt class="text-gray-500 mb-1">Profession</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.pref_profession }}</dd></div>
                            <div v-if="selectedProfile.pref_location"><dt class="text-gray-500 mb-1">Location</dt> <dd class="font-bold text-gray-900">{{ selectedProfile.pref_location }}</dd></div>
                            <div class="sm:col-span-2" v-if="selectedProfile.other_expectations"><dt class="text-gray-500 mb-1">Other Expectations</dt> <dd class="font-medium text-gray-900 whitespace-pre-line">{{ selectedProfile.other_expectations }}</dd></div>
                        </dl>
                    </div>

                    <div class="md:col-span-2 pt-5 flex flex-wrap justify-end gap-3 border-t border-gray-100">
                        <button
                            v-if="selectedProfile.status !== 'approved'"
                            @click="updateStatus(selectedProfile.id, 'approved')"
                            :disabled="processingId === selectedProfile.id"
                            class="px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-xs hover:bg-green-700 transition-colors disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            {{ processingId === selectedProfile.id && processingStatus === 'approved' ? 'Approving...' : 'Approve Profile' }}
                        </button>
                        <button
                            v-if="selectedProfile.status !== 'rejected'"
                            @click="updateStatus(selectedProfile.id, 'rejected')"
                            :disabled="processingId === selectedProfile.id"
                            class="px-5 py-2.5 bg-red-600 text-white font-bold rounded-lg shadow-xs hover:bg-red-700 transition-colors disabled:cursor-not-allowed disabled:bg-gray-300"
                        >
                            {{ processingId === selectedProfile.id && processingStatus === 'rejected' ? 'Rejecting...' : 'Reject Profile' }}
                        </button>
                        <button @click="destroy(selectedProfile.id)" :disabled="deleteForm.processing" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition-colors disabled:cursor-not-allowed disabled:bg-gray-100">Delete Profile</button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
