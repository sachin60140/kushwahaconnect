<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import FilterToggle from '@/Components/FilterToggle.vue';
import { useLanguage } from '@/composables/useLanguage';
import { GENDERS, paginationLabel, placeName } from '@/utils/optionLabels';
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    members: Object,
    states: Array,
    filters: Object
});

const { __, locale } = useLanguage();

const showAdvancedFilters = ref(!!(props.filters.gender || props.filters.skills));
const filtersOpen = ref(false);

const activeFilterCount = computed(() =>
    ['search', 'state_id', 'district_id', 'occupation', 'gender', 'skills'].filter((key) => props.filters[key]).length,
);

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
            const message = page.props.flash?.error || page.props.flash?.status;
            alert(message ? __(message) : __('dir_request_sent'));
        },
        onError: () => {
            alert(__('dir_request_failed'));
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

const memberPlace = (member) =>
    [placeName(locale.value, member.district), placeName(locale.value, member.state)].filter(Boolean).join(', ');
</script>

<template>
    <SeoMeta
        title="Community Directory"
        description="Search and connect with thousands of Kushwaha community members. Find professionals, business owners, and local leaders."
    />

    <PublicLayout :show-flash="false">
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('dir_eyebrow') }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('dir_title') }}</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('dir_intro') }}</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FilterToggle :open="filtersOpen" :count="activeFilterCount" controls="directory-filters" @toggle="filtersOpen = !filtersOpen" />

                <!-- Filters -->
                <div id="directory-filters" class="bg-white overflow-hidden shadow-xs sm:rounded-lg mb-6 p-6" :class="filtersOpen ? '' : 'hidden md:block'">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="dir-search" class="block text-sm font-medium text-gray-700">{{ __('dir_search_name') }}</label>
                            <input id="dir-search" type="text" v-model="form.search" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('dir_search_placeholder')">
                        </div>
                        <div>
                            <label for="dir-state" class="block text-sm font-medium text-gray-700">{{ __('join_state') }}</label>
                            <select id="dir-state" v-model="form.state_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('dir_select_state') }}</option>
                                <option v-for="state in states" :key="state.id" :value="state.id">{{ placeName(locale, state) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="dir-district" class="block text-sm font-medium text-gray-700">{{ __('join_district') }}</label>
                            <select id="dir-district" v-model="form.district_id" :disabled="!districts.length" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('dir_select_district') }}</option>
                                <option v-for="district in districts" :key="district.id" :value="district.id">{{ placeName(locale, district) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="dir-occupation" class="block text-sm font-medium text-gray-700">{{ __('dir_occupation') }}</label>
                            <input id="dir-occupation" type="text" v-model="form.occupation" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('dir_occupation_placeholder')">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center py-2 px-4 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 md:min-h-0 md:flex-none">
                                {{ __('common_filter') }}
                            </button>
                            <button type="button" @click="clearSearch" class="inline-flex min-h-11 flex-1 items-center justify-center py-2 px-4 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 md:min-h-0 md:flex-none">
                                {{ __('common_clear') }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <button type="button" @click="showAdvancedFilters = !showAdvancedFilters" :aria-expanded="showAdvancedFilters ? 'true' : 'false'" class="text-sm text-indigo-600 hover:text-indigo-900 font-medium flex min-h-11 items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{'rotate-180': showAdvancedFilters}">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                            {{ showAdvancedFilters ? __('common_hide_advanced_filters') : __('common_show_advanced_filters') }}
                        </button>

                        <div v-show="showAdvancedFilters" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                            <div>
                                <label for="dir-gender" class="block text-sm font-medium text-gray-700">{{ __('join_gender') }}</label>
                                <select id="dir-gender" v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">{{ __('dir_all_genders') }}</option>
                                    <option v-for="option in GENDERS" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="dir-skills" class="block text-sm font-medium text-gray-700">{{ __('dir_skills') }}</label>
                                <input id="dir-skills" type="text" v-model="form.skills" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('dir_skills_placeholder')">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <div v-for="member in members.data" :key="member.id" class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-center mb-4">
                                <div v-if="member.profile_photo" class="w-24 h-24 rounded-full overflow-hidden border-4 border-orange-100 shadow-xs">
                                    <img :src="'/storage/' + member.profile_photo" :alt="member.full_name" class="w-full h-full object-cover">
                                </div>
                                <div v-else class="w-24 h-24 bg-gray-200 rounded-full overflow-hidden flex items-center justify-center text-gray-400 border-4 border-gray-100 shadow-xs">
                                    <span class="text-2xl font-bold uppercase">{{ member.full_name.charAt(0) }}</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-gray-900">{{ member.full_name }}</h3>
                                <p class="text-sm text-gray-500">{{ memberPlace(member) }}</p>
                            </div>
                            <div class="mt-4 space-y-2 text-sm text-gray-600 border-t border-gray-100 pt-4">
                                <p v-if="member.occupation"><span class="font-medium text-gray-900">{{ __('dir_occupation') }}:</span> {{ member.occupation }}</p>
                                <p v-if="member.skills"><span class="font-medium text-gray-900">{{ __('dir_skills') }}:</span> {{ member.skills }}</p>
                            </div>

                            <!-- Connect Button -->
                            <div class="mt-6 text-center" v-if="member.user_id && $page.props.auth.user && $page.props.auth.user.id !== member.user_id">
                                <button
                                    @click="sendConnectionRequest(member.user_id)"
                                    :disabled="connectForm.processing && connectForm.receiver_id === member.user_id"
                                    class="w-full inline-flex min-h-11 justify-center items-center px-4 py-2 bg-orange-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-700 active:bg-orange-900 focus:outline-hidden focus:border-orange-900 focus:ring-3 ring-orange-300 disabled:opacity-25 transition ease-in-out duration-150"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    {{ connectForm.processing && connectForm.receiver_id === member.user_id ? __('otp_sending') : __('btn_connect') }}
                                </button>
                            </div>
                            <div class="mt-6 text-center" v-else-if="!$page.props.auth.user">
                                <Link :href="route('login')" class="w-full inline-flex min-h-11 justify-center items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-hidden focus:border-gray-900 focus:ring-3 ring-gray-300 transition ease-in-out duration-150">
                                    {{ __('dir_login_to_connect') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="members.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex flex-wrap justify-center shadow-xs rounded-md">
                        <template v-for="(link, p) in members.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="paginationLabel(__, link.label)" />
                            <Link v-else class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="paginationLabel(__, link.label)" />
                        </template>
                    </div>
                </div>

                <div v-if="members.data.length === 0" class="text-center py-12 px-4 bg-white rounded-lg shadow-xs">
                    <p class="text-lg font-medium text-gray-900">{{ __('dir_empty') }}</p>
                    <p class="mt-1 text-gray-500">{{ __('dir_empty_hint') }}</p>
                    <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <button v-if="activeFilterCount" type="button" @click="clearSearch" class="kc-btn-secondary justify-center">
                            {{ __('common_clear_filters') }}
                        </button>
                        <Link v-if="!$page.props.auth.user" :href="route('join')" class="kc-btn-primary justify-center">
                            {{ __('btn_join') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
