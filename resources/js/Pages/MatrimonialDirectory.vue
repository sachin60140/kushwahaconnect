<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { useLanguage } from '@/composables/useLanguage';
import { GENDERS, MAT_MANGLIK, MAT_MARITAL_STATUSES, optionLabel, paginationLabel } from '@/utils/optionLabels';
import { computed, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    profiles: Object,
    filters: Object
});

const { __ } = useLanguage();

// Same choices as before the translation; values are what the server filters on.
const genderOptions = GENDERS.filter((option) => option.value !== 'Other');
const maritalOptions = MAT_MARITAL_STATUSES.filter((option) => option.value !== 'Annulled');

const showAdvancedFilters = ref(!!(props.filters.min_age || props.filters.max_age || props.filters.manglik || props.filters.occupation));
const activeFilterCount = computed(() => Object.values(props.filters || {}).filter(Boolean).length);

const form = useForm({
    gender: props.filters.gender || '',
    marital_status: props.filters.marital_status || '',
    education: props.filters.education || '',
    city: props.filters.city || '',
    min_age: props.filters.min_age || '',
    max_age: props.filters.max_age || '',
    manglik: props.filters.manglik || '',
    occupation: props.filters.occupation || ''
});

const submitSearch = () => {
    form.get(route('matrimonial-directory.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    form.gender = '';
    form.marital_status = '';
    form.education = '';
    form.city = '';
    form.min_age = '';
    form.max_age = '';
    form.manglik = '';
    form.occupation = '';
    submitSearch();
};

const toggleShortlist = async (profile) => {
    try {
        const response = await axios.post(route('matrimonial-directory.shortlist', profile.id));
        if (response.data.status === 'added') {
            profile.is_shortlisted = true;
        } else if (response.data.status === 'removed') {
            profile.is_shortlisted = false;
        }
    } catch (error) {
        console.error('Error toggling shortlist:', error);
    }
};
</script>

<template>
    <SeoMeta 
        title="Matrimonial Directory" 
        description="Find your perfect life partner within the Kushwaha community. Browse verified matrimonial profiles."
    />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">{{ __('mat_eyebrow') }}</p>
                <h2 class="mt-1 text-2xl font-black text-blue-950">{{ __('mat_dir_title') }}</h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="kc-surface mb-6 p-6">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                        <div>
                            <label for="md-gender" class="block text-sm font-medium text-gray-700">{{ __('join_gender') }}</label>
                            <select id="md-gender" v-model="form.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('common_all') }}</option>
                                <option v-for="option in genderOptions" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="md-marital" class="block text-sm font-medium text-gray-700">{{ __('mat_marital_status') }}</label>
                            <select id="md-marital" v-model="form.marital_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">{{ __('common_all') }}</option>
                                <option v-for="option in maritalOptions" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="md-education" class="block text-sm font-medium text-gray-700">{{ __('mat_dir_education') }}</label>
                            <input id="md-education" type="text" v-model="form.education" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('mat_dir_education_placeholder')">
                        </div>
                        <div>
                            <label for="md-city" class="block text-sm font-medium text-gray-700">{{ __('mat_dir_city') }}</label>
                            <input id="md-city" type="text" v-model="form.city" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('mat_dir_city_placeholder')">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center md:min-h-0 md:flex-none py-2 px-4 border border-transparent shadow-xs text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('common_search') }}
                            </button>
                            <button type="button" @click="clearSearch" class="inline-flex min-h-11 flex-1 items-center justify-center md:min-h-0 md:flex-none py-2 px-4 border border-gray-300 shadow-xs text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
                        
                        <div v-show="showAdvancedFilters" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <label for="md-min-age" class="block text-sm font-medium text-gray-700">{{ __('mat_dir_min_age') }}</label>
                                <input id="md-min-age" type="number" min="18" v-model="form.min_age" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('mat_dir_min_age_placeholder')">
                            </div>
                            <div>
                                <label for="md-max-age" class="block text-sm font-medium text-gray-700">{{ __('mat_dir_max_age') }}</label>
                                <input id="md-max-age" type="number" min="18" v-model="form.max_age" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('mat_dir_max_age_placeholder')">
                            </div>
                            <div>
                                <label for="md-manglik" class="block text-sm font-medium text-gray-700">{{ __('mat_manglik') }}</label>
                                <select id="md-manglik" v-model="form.manglik" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">{{ __('common_all') }}</option>
                                    <option v-for="option in MAT_MANGLIK" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="md-occupation" class="block text-sm font-medium text-gray-700">{{ __('dir_occupation') }}</label>
                                <input id="md-occupation" type="text" v-model="form.occupation" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" :placeholder="__('dir_occupation_placeholder')">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Directory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="profile in profiles.data" :key="profile.id" class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="w-20 h-20 bg-gray-200 rounded-full overflow-hidden">
                                    <img v-if="profile.photo_url && !profile.hide_photo" :src="profile.photo_url" class="w-full h-full object-cover" :alt="profile.full_name">
                                    <svg v-else class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                      <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ profile.full_name }}</h3>
                                    <p class="text-sm text-gray-500">{{ profile.dob }} • {{ optionLabel(__, GENDERS, profile.gender) }}</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p><span class="font-medium text-gray-900">{{ __('mat_dir_education') }}:</span> {{ profile.education || __('common_not_available') }}</p>
                                <p><span class="font-medium text-gray-900">{{ __('dir_occupation') }}:</span> {{ profile.occupation || __('common_not_available') }}</p>
                                <p><span class="font-medium text-gray-900">{{ __('common_location') }}:</span> {{ profile.current_city || __('common_not_available') }}</p>
                                <p><span class="font-medium text-gray-900">{{ __('mat_marital_status') }}:</span> {{ optionLabel(__, MAT_MARITAL_STATUSES, profile.marital_status) }}</p>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-between items-center">
                            <a v-if="profile.biodata_url" :href="profile.biodata_url" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center text-green-600 hover:text-green-800 font-medium text-sm">{{ __('mat_dir_view_biodata') }}</a>
                            <span v-else class="text-xs text-gray-400" :title="__('mat_dir_biodata_title')">{{ __('mat_dir_biodata_later') }}</span>
                            <button @click="toggleShortlist(profile)" type="button" class="flex min-h-11 shrink-0 items-center gap-1 focus:outline-hidden transition-colors duration-200" :class="profile.is_shortlisted ? 'text-red-500 hover:text-red-600' : 'text-gray-400 hover:text-red-500'">
                                <svg v-if="profile.is_shortlisted" class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                <span class="text-sm font-medium">{{ profile.is_shortlisted ? __('mat_dir_saved') : __('mat_dir_shortlist') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="profiles.links.length > 3" class="mt-6 flex justify-center">
                    <div class="flex flex-wrap justify-center shadow-xs rounded-md">
                        <template v-for="(link, p) in profiles.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="paginationLabel(__, link.label)" />
                            <Link v-else class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="paginationLabel(__, link.label)" />
                        </template>
                    </div>
                </div>
                
                <div v-if="profiles.data.length === 0" class="text-center py-12 px-4 bg-white rounded-lg shadow-xs">
                    <p class="text-lg font-medium text-gray-900">{{ __('mat_dir_empty') }}</p>
                    <p class="mt-1 text-gray-500">{{ __('mat_dir_empty_hint') }}</p>
                    <button v-if="activeFilterCount" type="button" @click="clearSearch" class="kc-btn-secondary mt-5">
                        {{ __('common_clear_filters') }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
