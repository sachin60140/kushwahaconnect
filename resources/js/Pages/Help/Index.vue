<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HelpShareButtons from '@/Components/HelpShareButtons.vue';
import FilterToggle from '@/Components/FilterToggle.vue';
import { toWhatsAppNumber } from '@/composables/useWhatsAppWelcome';
import { useLanguage } from '@/composables/useLanguage';
import { HELP_CATEGORIES, HELP_URGENCIES, optionLabel, paginationLabel } from '@/utils/optionLabels';

const props = defineProps({
    requests: Object,
    filters: Object
});

const { __ } = useLanguage();
const page = usePage();
const user = computed(() => page.props.auth.user);

const filtersOpen = ref(false);
const activeFilterCount = computed(() =>
    ['category', 'urgency', 'state', 'city'].filter((key) => props.filters[key]).length,
);

const form = useForm({
    category: props.filters.category || '',
    urgency: props.filters.urgency || '',
    state: props.filters.state || '',
    city: props.filters.city || ''
});

const submitSearch = () => {
    form.get(route('help.index'), {
        preserveState: true,
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Help Each Other - KushwahaConnect">
        <meta name="description" content="Request assistance or offer help to community members in need. Financial, medical, and general support." />
        <meta name="keywords" content="help, community support, donation, assistance, kushwaha" />
    </Head>

    <component :is="user ? AuthenticatedLayout : PublicLayout">
        <template #header v-if="user">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">{{ __('help_eyebrow') }}</p>
                    <h2 class="mt-1 text-2xl font-black text-blue-950">{{ __('help_title') }}</h2>
                </div>
                <Link :href="route('help.create')" class="kc-btn-primary shrink-0">
                    {{ __('btn_request_help') }}
                </Link>
            </div>
        </template>

        <template #header v-else>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('help_eyebrow') }}</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('help_title') }}</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('help_index_intro') }}</p>
                </div>
                <Link :href="route('help.create')" class="kc-btn-primary shrink-0">
                    {{ __('btn_request_help') }}
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <FilterToggle :open="filtersOpen" :count="activeFilterCount" controls="help-filters" @toggle="filtersOpen = !filtersOpen" />

                <!-- Filters -->
                <div id="help-filters" class="bg-white overflow-hidden shadow-sm sm:rounded-xl mb-8 p-6" :class="filtersOpen ? '' : 'hidden md:block'">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label for="help-category" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common_category') }}</label>
                            <select id="help-category" v-model="form.category" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200">
                                <option value="">{{ __('common_all_categories') }}</option>
                                <option v-for="option in HELP_CATEGORIES" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="help-urgency" class="block text-sm font-medium text-gray-700 mb-1">{{ __('help_urgency') }}</label>
                            <select id="help-urgency" v-model="form.urgency" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200">
                                <option value="">{{ __('help_all_priorities') }}</option>
                                <option v-for="option in HELP_URGENCIES" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="help-city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('help_location') }}</label>
                            <input id="help-city" type="text" v-model="form.city" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-3 transition-colors duration-200" :placeholder="__('help_location_placeholder')">
                        </div>
                        <div>
                            <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-xs text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-hidden transition-colors duration-200">
                                {{ __('help_filter_requests') }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Requests Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="req in requests.data" :key="req.id" class="bg-white overflow-hidden shadow-md sm:rounded-xl flex flex-col border-t-4 hover:shadow-lg transition-shadow duration-300"
                         :class="{
                             'border-red-500': req.urgency === 'Emergency',
                             'border-orange-500': req.urgency === 'Urgent',
                             'border-green-500': req.urgency === 'Normal'
                         }">
                        <div class="p-6 flex-1 flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800">
                                    {{ optionLabel(__, HELP_CATEGORIES, req.category) }}
                                </span>
                                <span class="px-3 py-1 text-xs font-bold rounded-full"
                                      :class="{
                                          'bg-red-100 text-red-800 border border-red-200': req.urgency === 'Emergency',
                                          'bg-orange-100 text-orange-800 border border-orange-200': req.urgency === 'Urgent',
                                          'bg-green-100 text-green-800 border border-green-200': req.urgency === 'Normal'
                                      }">
                                    {{ optionLabel(__, HELP_URGENCIES, req.urgency) }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">
                                <Link :href="route('help.show', req.id)" class="inline-flex min-h-11 items-center hover:text-green-700 hover:underline md:min-h-0">{{ req.title }}</Link>
                            </h3>
                            <p class="text-sm font-medium text-gray-500 mb-4">{{ req.city }}, {{ req.state }}</p>

                            <p class="text-gray-700 text-sm mb-4 flex-1 whitespace-pre-line leading-relaxed">{{ req.description }}</p>

                            <div v-if="req.documents_count || req.bank_account_number || req.upi_id || req.upi_qr_url" class="flex flex-wrap gap-2 text-xs font-bold">
                                <Link v-if="req.documents_count" :href="route('help.show', req.id)" class="inline-flex min-h-11 items-center rounded-full bg-blue-50 px-3 py-1 text-blue-700 hover:bg-blue-100 md:min-h-0 md:px-2.5">
                                    📎 {{ req.documents_count }} {{ req.documents_count > 1 ? __('help_documents_many') : __('help_documents_one') }}
                                </Link>
                                <Link v-if="req.bank_account_number || req.upi_id || req.upi_qr_url" :href="route('help.show', req.id)" class="inline-flex min-h-11 items-center rounded-full bg-green-50 px-3 py-1 text-green-700 hover:bg-green-100 md:min-h-0 md:px-2.5">
                                    💳 {{ __('help_bank_upi_badge') }}
                                </Link>
                            </div>

                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-900 text-sm">{{ __('help_requested_by') }} <span class="text-green-700">{{ req.name }}</span></h4>
                                    <span class="text-xs font-medium text-gray-500">{{ new Date(req.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex space-x-3">
                                    <a :href="`tel:${req.mobile}`" class="flex-1 min-h-11 text-center bg-gray-50 border border-gray-200 text-gray-700 hover:bg-green-50 hover:text-green-700 hover:border-green-200 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        {{ __('common_call') }}
                                    </a>
                                    <a v-if="toWhatsAppNumber(req.whatsapp)" :href="`https://wa.me/${toWhatsAppNumber(req.whatsapp)}`" target="_blank" rel="noopener noreferrer" class="flex-1 min-h-11 text-center bg-green-500 text-white hover:bg-green-600 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center shadow-xs transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.016 1.996C6.486 1.996 2 6.481 2 12.012c0 1.945.541 3.753 1.464 5.289l-1.572 5.733 5.854-1.536A9.972 9.972 0 0012.016 22.02c5.53 0 10.016-4.485 10.016-10.008 0-5.53-4.486-10.016-10.016-10.016zm5.836 14.215c-.244.693-1.42 1.328-1.993 1.408-.415.059-1.02.136-2.912-.646-2.235-.92-3.666-3.19-3.774-3.333-.108-.144-.902-1.2-1.002-2.316-.08-1.116.516-1.637.7-1.84.183-.203.468-.26.653-.26.185 0 .37.004.536.012.185.008.435-.068.68.536.257.632.846 2.065.92 2.215.074.15.122.327.025.518-.097.19-.148.308-.295.48-.147.17-.308.384-.442.52-.147.15-.3.313-.122.62.178.307.794 1.314 1.708 2.13.194.347.534.613.916.748.382.135.608.113.84-.047.23-.16.994-1.162 1.264-1.562.27-.4.542-.332.906-.195.364.137 2.3 1.085 2.694 1.282.394.197.656.295.753.46.098.165.098.96-.146 1.653z" clip-rule="evenodd"></path></svg>
                                        WhatsApp
                                    </a>
                                </div>
                                <HelpShareButtons :help-request="req" compact class="mt-4 pt-3 border-t border-gray-100" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex flex-wrap justify-center shadow-xs rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in requests.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 text-gray-400" v-html="paginationLabel(__, link.label)" />
                            <Link v-else class="mr-1 mb-1 inline-flex min-h-11 items-center px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-green-600 transition-colors duration-200" :class="{ 'bg-green-50 font-bold text-green-700 border-b-2 border-green-600': link.active }" :href="link.url" v-html="paginationLabel(__, link.label)" />
                        </template>
                    </div>
                </div>

                <div v-if="requests.data.length === 0" class="text-center py-12 px-4 bg-white rounded-xl shadow-xs border border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">{{ __('help_empty') }}</p>
                    <p class="mt-1 text-gray-500">{{ __('help_empty_hint') }}</p>
                    <Link :href="route('help.create')" class="kc-btn-primary mt-5">
                        {{ __('btn_request_help') }}
                    </Link>
                </div>
            </div>
        </div>
    </component>
</template>
