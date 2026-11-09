<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    members: Object,
    states: Array,
    filters: Object
});

const showAdvancedFilters = ref(false);

const form = useForm({
    search: props.filters.search || '',
    state_id: props.filters.state_id || '',
    district_id: props.filters.district_id || '',
    occupation: props.filters.occupation || '',
    gender: props.filters.gender || '',
    skills: props.filters.skills || ''
});

const connectForm = useForm({
    receiver_id: ''
});

const districts = ref([]);
const page = usePage();

const sendConnectionRequest = (userId) => {
    connectForm.receiver_id = userId;
    connectForm.post(route('user.connections.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // The controller redirects back with flash.error when the request is refused
            // (already connected / pending / self), which still counts as a "success" visit.
            const error = page.props.flash?.error;
            alert(error || page.props.flash?.status || 'Connection request sent!');
        },
        onError: () => {
            alert('Could not send request. You might already be connected or have a pending request.');
        }
    });
};

const fetchDistricts = async () => {
    if (!form.state_id) {
        districts.value = [];
        return;
    }

    const response = await axios.get(`/api/states/${form.state_id}/districts`);
    districts.value = response.data;
};

watch(() => form.state_id, () => {
    // A district from the previous state is never valid for the new one.
    form.district_id = '';
    fetchDistricts();
});

// Load districts for a state that came from the URL so the district filter is usable.
onMounted(fetchDistricts);

const submitSearch = () => {
    form.get(route('directory'), {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    form.search = '';
    form.state_id = '';
    form.district_id = '';
    form.occupation = '';
    form.gender = '';
    form.skills = '';
    districts.value = [];
    submitSearch();
};
</script>

<template>
    <SeoMeta 
        title="Community Directory" 
        description="Search and connect with thousands of Kushwaha community members. Find professionals, business owners, and local leaders."
    />

    <PublicLayout :show-flash="false">
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Community Network</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Community Member Directory</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Search approved public profiles and connect with Kushwaha Samaj members safely.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-6">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Search Name</label>
                            <input type="text" v-model="form.search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Name...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">State</label>
                            <select v-model="form.state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select State</option>
                                <option v-for="state in states" :key="state.id" :value="state.id">{{ state.name_en }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">District</label>
                            <select v-model="form.district_id" :disabled="!districts.length" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Select District</option>
                                <option v-for="district in districts" :key="district.id" :value="district.id">{{ district.name_en }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Occupation</label>
                            <input type="text" v-model="form.occupation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Engineer">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Filter
                            </button>
                            <button type="button" @click="clearSearch" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Clear
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <button type="button" @click="showAdvancedFilters = !showAdvancedFilters" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{'rotate-180': showAdvancedFilters}">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            {{ showAdvancedFilters ? 'Hide Advanced Filters' : 'Show Advanced Filters' }}
                        </button>
                        
                        <div v-show="showAdvancedFilters" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gender</label>
                                <select v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Skills</label>
                                <input type="text" v-model="form.skills" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Accounting, Web Design">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="member in members.data" :key="member.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-center mb-4">
                                <div v-if="member.profile_photo" class="w-24 h-24 rounded-full overflow-hidden border-4 border-orange-100 shadow-sm">
                                    <img :src="'/storage/' + member.profile_photo" :alt="member.full_name" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-24 h-24 bg-gray-200 rounded-full overflow-hidden flex items-center justify-center text-gray-400 border-4 border-gray-100 shadow-sm">
                                    <span class="text-2xl font-bold uppercase">{{ member.full_name.charAt(0) }}</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-gray-900">{{ member.full_name }}</h3>
                                <p class="text-sm text-gray-500">{{ member.district?.name_en || '' }}, {{ member.state?.name_en || '' }}</p>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-gray-600 border-t border-gray-100 pt-4">
                                <p v-if="member.occupation"><span class="font-medium text-gray-900">Occupation:</span> {{ member.occupation }}</p>
                                <p v-if="member.skills"><span class="font-medium text-gray-900">Skills:</span> {{ member.skills }}</p>
                            </div>
                            
                            <!-- Connect Button -->
                            <div class="mt-6 text-center" v-if="member.user_id && $page.props.auth.user && $page.props.auth.user.id !== member.user_id">
                                <button 
                                    @click="sendConnectionRequest(member.user_id)" 
                                    :disabled="connectForm.processing && connectForm.receiver_id === member.user_id"
                                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 active:bg-orange-900 focus:outline-none focus:border-orange-900 focus:ring ring-orange-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    {{ connectForm.processing && connectForm.receiver_id === member.user_id ? 'Sending...' : 'Connect' }}
                                </button>
                            </div>
                            <div class="mt-6 text-center" v-else-if="!$page.props.auth.user">
                                <Link :href="route('login')" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 transition ease-in-out duration-150">
                                    Login to Connect
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="members.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex shadow-sm rounded-md">
                        <template v-for="(link, p) in members.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="members.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow-sm">
                    <p class="text-gray-500">No members found matching your criteria.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
