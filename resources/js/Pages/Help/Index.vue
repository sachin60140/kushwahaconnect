<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object
});

const page = usePage();
const user = computed(() => page.props.auth.user);

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
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">Community Support</p>
                <h2 class="mt-1 text-2xl font-black text-blue-950">Help Each Other</h2>
            </div>
        </template>
        
        <template #header v-else>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Community Support</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Help Each Other</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">Request assistance or offer help to community members in need.</p>
                </div>
                <Link :href="route('help.create')" class="kc-btn-primary shrink-0">
                    Request Help
                </Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <div class="bg-white overflow-hidden shadow sm:rounded-xl mb-8 p-6">
                    <form @submit.prevent="submitSearch" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select v-model="form.category" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-3 transition-colors duration-200">
                                <option value="">All Categories</option>
                                <option value="Education Help">Education Help</option>
                                <option value="Job Help">Job Help</option>
                                <option value="Business Help">Business Help</option>
                                <option value="Medical Help">Medical Help</option>
                                <option value="Emergency Help">Emergency Help</option>
                                <option value="Government Scheme Help">Government Scheme Help</option>
                                <option value="Legal Guidance">Legal Guidance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urgency</label>
                            <select v-model="form.urgency" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-3 transition-colors duration-200">
                                <option value="">All Priorities</option>
                                <option value="Normal">Normal</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location (State/City)</label>
                            <input type="text" v-model="form.city" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-3 transition-colors duration-200" placeholder="e.g. Delhi, Bihar">
                        </div>
                        <div>
                            <button type="submit" class="w-full inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-base font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none transition-colors duration-200">
                                Filter Requests
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
                                    {{ req.category }}
                                </span>
                                <span class="px-3 py-1 text-xs font-bold rounded-full"
                                      :class="{
                                          'bg-red-100 text-red-800 border border-red-200': req.urgency === 'Emergency',
                                          'bg-orange-100 text-orange-800 border border-orange-200': req.urgency === 'Urgent',
                                          'bg-green-100 text-green-800 border border-green-200': req.urgency === 'Normal'
                                      }">
                                    {{ req.urgency }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-1">{{ req.title }}</h3>
                            <p class="text-sm font-medium text-gray-500 mb-4">{{ req.city }}, {{ req.state }}</p>
                            
                            <p class="text-gray-700 text-sm mb-4 flex-1 whitespace-pre-line leading-relaxed">{{ req.description }}</p>
                            
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-semibold text-gray-900 text-sm">Requested by: <span class="text-green-700">{{ req.name }}</span></h4>
                                    <span class="text-xs font-medium text-gray-500">{{ new Date(req.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div class="flex space-x-3">
                                    <a :href="`tel:${req.mobile}`" class="flex-1 text-center bg-gray-50 border border-gray-200 text-gray-700 hover:bg-green-50 hover:text-green-700 hover:border-green-200 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        Call
                                    </a>
                                    <a v-if="req.whatsapp" :href="`https://wa.me/${req.whatsapp.replace(/[^0-9]/g, '')}`" target="_blank" class="flex-1 text-center bg-green-500 text-white hover:bg-green-600 text-sm font-bold py-2 px-4 rounded-lg flex items-center justify-center shadow-sm transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12.016 1.996C6.486 1.996 2 6.481 2 12.012c0 1.945.541 3.753 1.464 5.289l-1.572 5.733 5.854-1.536A9.972 9.972 0 0012.016 22.02c5.53 0 10.016-4.485 10.016-10.008 0-5.53-4.486-10.016-10.016-10.016zm5.836 14.215c-.244.693-1.42 1.328-1.993 1.408-.415.059-1.02.136-2.912-.646-2.235-.92-3.666-3.19-3.774-3.333-.108-.144-.902-1.2-1.002-2.316-.08-1.116.516-1.637.7-1.84.183-.203.468-.26.653-.26.185 0 .37.004.536.012.185.008.435-.068.68.536.257.632.846 2.065.92 2.215.074.15.122.327.025.518-.097.19-.148.308-.295.48-.147.17-.308.384-.442.52-.147.15-.3.313-.122.62.178.307.794 1.314 1.708 2.13.194.347.534.613.916.748.382.135.608.113.84-.047.23-.16.994-1.162 1.264-1.562.27-.4.542-.332.906-.195.364.137 2.3 1.085 2.694 1.282.394.197.656.295.753.46.098.165.098.96-.146 1.653z" clip-rule="evenodd"></path></svg>
                                        WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="requests.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex shadow-sm rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in requests.links" :key="p">
                            <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 hover:bg-gray-50 focus:text-green-600 transition-colors duration-200" :class="{ 'bg-green-50 font-bold text-green-700 border-b-2 border-green-600': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
                
                <div v-if="requests.data.length === 0" class="text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">No help requests found matching your criteria.</p>
                </div>
            </div>
        </div>
    </component>
</template>
