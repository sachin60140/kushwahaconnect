<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import FilterToggle from '@/Components/FilterToggle.vue';
import { useLanguage } from '@/composables/useLanguage';
import { VOLUNTEER_AREAS, VOLUNTEER_TIMES, optionLabel, paginationLabel } from '@/utils/optionLabels';
import { computed, ref } from 'vue';

const props = defineProps({
    volunteers: Object,
    filters: Object
});

const { __ } = useLanguage();

const filtersOpen = ref(false);
const activeFilterCount = computed(() =>
    ['state', 'district', 'help_area'].filter((key) => props.filters[key]).length,
);

const form = useForm({
    state: props.filters.state || '',
    district: props.filters.district || '',
    help_area: props.filters.help_area || ''
});

const submitSearch = () => {
    form.get(route('volunteer.index'), {
        preserveState: true,
        preserveScroll: true
    });
};

const clearSearch = () => {
    form.state = '';
    form.district = '';
    form.help_area = '';
    submitSearch();
};
</script>

<template>
    <Head title="Volunteer Directory" />

    <PublicLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('help_title') }}</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('vol_network') }}</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('vol_index_intro') }}</p>
                </div>
                <Link :href="route('volunteer.create')" class="kc-btn-primary shrink-0">
                    {{ __('vol_join_as_volunteer') }}
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FilterToggle :open="filtersOpen" :count="activeFilterCount" controls="volunteer-filters" @toggle="filtersOpen = !filtersOpen" />

                <!-- Filters -->
                <div id="volunteer-filters" class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-8 p-6" :class="filtersOpen ? '' : 'hidden md:block'">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="vol-state" class="block text-sm font-medium text-gray-700 mb-1">{{ __('join_state') }}</label>
                            <input id="vol-state" type="text" v-model="form.state" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200" :placeholder="__('vol_state_placeholder')">
                        </div>
                        <div>
                            <label for="vol-district" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vol_district_city') }}</label>
                            <input id="vol-district" type="text" v-model="form.district" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200" :placeholder="__('vol_district_placeholder')">
                        </div>
                        <div>
                            <label for="vol-area" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vol_area_of_help') }}</label>
                            <select id="vol-area" v-model="form.help_area" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200">
                                <option value="">{{ __('vol_all_areas') }}</option>
                                <option v-for="option in VOLUNTEER_AREAS" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="flex-1 inline-flex justify-center py-3 px-6 border border-transparent shadow-xs text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-hidden transition-colors duration-200">
                                {{ __('common_filter') }}
                            </button>
                            <button type="button" @click="clearSearch" class="inline-flex justify-center py-3 px-6 border border-gray-300 shadow-xs text-base font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-hidden transition-colors duration-200">
                                {{ __('common_clear') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Directory Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="volunteer in volunteers.data" :key="volunteer.id" class="bg-white overflow-hidden shadow-md sm:rounded-xl hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 text-xl font-bold border-2 border-orange-200">
                                    {{ volunteer.name.charAt(0) }}
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-bold text-gray-900">{{ volunteer.name }}</h3>
                                    <p class="text-sm font-medium text-gray-500">{{ volunteer.city }}, {{ volunteer.state }}</p>
                                </div>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('vol_areas_of_help') }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="area in volunteer.help_areas" :key="area" class="px-2.5 py-1 bg-green-50 text-green-700 rounded-md text-xs font-medium border border-green-200">
                                            {{ optionLabel(__, VOLUNTEER_AREAS, area) }}
                                        </span>
                                    </div>
                                </div>
                                <div v-if="volunteer.skills">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('dir_skills') }}</h4>
                                    <p class="text-sm text-gray-800">{{ volunteer.skills }}</p>
                                </div>
                                <div v-if="volunteer.available_time">
                                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">{{ __('vol_availability') }}</h4>
                                    <p class="text-sm text-gray-800">{{ optionLabel(__, VOLUNTEER_TIMES, volunteer.available_time) }}</p>
                                </div>
                            </div>
                            
                            <div class="mt-auto pt-4 border-t border-gray-100 flex space-x-3">
                                <a :href="`tel:${volunteer.mobile}`" class="flex-1 min-h-11 text-center bg-gray-50 border border-gray-200 text-gray-700 hover:bg-green-50 hover:text-green-700 hover:border-green-200 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                    {{ __('common_call') }}
                                </a>
                                <a v-if="volunteer.whatsapp" :href="`https://wa.me/${volunteer.whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" rel="noopener noreferrer" class="flex-1 min-h-11 text-center bg-green-500 text-white hover:bg-green-600 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center shadow-xs transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.016 1.996C6.486 1.996 2 6.481 2 12.012c0 1.945.541 3.753 1.464 5.289l-1.572 5.733 5.854-1.536A9.972 9.972 0 0012.016 22.02c5.53 0 10.016-4.485 10.016-10.008 0-5.53-4.486-10.016-10.016-10.016zm5.836 14.215c-.244.693-1.42 1.328-1.993 1.408-.415.059-1.02.136-2.912-.646-2.235-.92-3.666-3.19-3.774-3.333-.108-.144-.902-1.2-1.002-2.316-.08-1.116.516-1.637.7-1.84.183-.203.468-.26.653-.26.185 0 .37.004.536.012.185.008.435-.068.68.536.257.632.846 2.065.92 2.215.074.15.122.327.025.518-.097.19-.148.308-.295.48-.147.17-.308.384-.442.52-.147.15-.3.313-.122.62.178.307.794 1.314 1.708 2.13.194.347.534.613.916.748.382.135.608.113.84-.047.23-.16.994-1.162 1.264-1.562.27-.4.542-.332.906-.195.364.137 2.3 1.085 2.694 1.282.394.197.656.295.753.46.098.165.098.96-.146 1.653z" clip-rule="evenodd"></path></svg>
                                    WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="volunteers.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex flex-wrap justify-center shadow-xs rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in volunteers.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 text-gray-400" v-html="paginationLabel(__, link.label)" />
                            <Link v-else class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-green-600 transition-colors duration-200" :class="{ 'bg-green-50 font-bold text-green-700 border-b-2 border-green-600': link.active }" :href="link.url" v-html="paginationLabel(__, link.label)" />
                        </template>
                    </div>
                </div>
                
                <div v-if="volunteers.data.length === 0" class="text-center py-12 px-4 bg-white rounded-xl shadow-xs border border-gray-200">
                    <p class="text-gray-500 text-lg font-medium">{{ __('vol_empty') }}</p>
                    <p class="mt-1 text-gray-500">{{ __('vol_empty_hint') }}</p>
                    <div class="mt-5 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <button v-if="activeFilterCount" type="button" @click="clearSearch" class="kc-btn-secondary justify-center">
                            {{ __('common_clear_filters') }}
                        </button>
                        <Link :href="route('volunteer.create')" class="kc-btn-primary justify-center">
                            {{ __('btn_become_volunteer') }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
