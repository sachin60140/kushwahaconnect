<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HelpShareButtons from '@/Components/HelpShareButtons.vue';
import HelpDonationBox from '@/Components/HelpDonationBox.vue';
import { toWhatsAppNumber } from '@/composables/useWhatsAppWelcome';

const props = defineProps({
    helpRequest: Object,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isHindi = computed(() => (page.props.locale || 'hi') === 'hi');

const location = computed(() =>
    [props.helpRequest.city, props.helpRequest.district, props.helpRequest.state]
        .filter(Boolean)
        .filter((value, index, all) => all.indexOf(value) === index)
        .join(', '),
);

const whatsappNumber = computed(() => toWhatsAppNumber(props.helpRequest.whatsapp));

const images = computed(() => (props.helpRequest.documents || []).filter((doc) => doc.is_image));
const files = computed(() => (props.helpRequest.documents || []).filter((doc) => !doc.is_image));

const urgencyClasses = computed(() => ({
    'bg-red-100 text-red-800 border border-red-200': props.helpRequest.urgency === 'Emergency',
    'bg-orange-100 text-orange-800 border border-orange-200': props.helpRequest.urgency === 'Urgent',
    'bg-green-100 text-green-800 border border-green-200': props.helpRequest.urgency === 'Normal',
}));
</script>

<template>
    <Head :title="helpRequest.title" />

    <component :is="user ? AuthenticatedLayout : PublicLayout">
        <template #header>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.18em]" :class="user ? 'text-orange-500' : 'text-orange-300'">
                        {{ isHindi ? 'मदद की अपील' : 'Help Request' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-black sm:text-3xl" :class="user ? 'text-blue-950' : 'text-white'">{{ helpRequest.title }}</h1>
                </div>
                <Link :href="route('help.index')" class="kc-btn-secondary shrink-0">
                    {{ isHindi ? '← सभी अनुरोध' : '← All requests' }}
                </Link>
            </div>
        </template>

        <div class="py-10 bg-gray-50 min-h-[60vh]">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <article
                    class="bg-white overflow-hidden shadow-md rounded-xl border-t-4"
                    :class="{
                        'border-red-500': helpRequest.urgency === 'Emergency',
                        'border-orange-500': helpRequest.urgency === 'Urgent',
                        'border-green-500': helpRequest.urgency === 'Normal',
                    }"
                >
                    <div class="p-6 sm:p-8">
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span class="px-3 py-1 text-xs font-bold rounded-full bg-gray-100 text-gray-800">{{ helpRequest.category }}</span>
                            <span class="px-3 py-1 text-xs font-bold rounded-full" :class="urgencyClasses">{{ helpRequest.urgency }}</span>
                            <span v-if="helpRequest.status === 'Resolved'" class="px-3 py-1 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                                {{ isHindi ? 'हल हो गया' : 'Resolved' }}
                            </span>
                        </div>

                        <p v-if="location" class="text-sm font-medium text-gray-500 mb-4">📍 {{ location }}</p>

                        <p class="text-gray-800 leading-relaxed whitespace-pre-line">{{ helpRequest.description }}</p>

                        <!-- Supporting documents / photos -->
                        <section v-if="helpRequest.documents?.length" class="mt-6">
                            <h2 class="text-sm font-black text-gray-900 mb-3">
                                {{ isHindi ? 'दस्तावेज़ और फ़ोटो' : 'Documents & photos' }}
                                <span class="font-medium text-gray-500">({{ helpRequest.documents.length }})</span>
                            </h2>
                            <div v-if="images.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                                <a v-for="doc in images" :key="doc.id" :href="doc.url" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                    <img :src="doc.url" :alt="doc.original_name" loading="lazy" class="h-32 w-full object-cover transition group-hover:scale-105">
                                </a>
                            </div>
                            <ul v-if="files.length" class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                                <li v-for="doc in files" :key="doc.id">
                                    <a :href="doc.url" target="_blank" rel="noopener" class="flex items-center justify-between gap-3 px-3 py-2 text-sm hover:bg-gray-50">
                                        <span class="min-w-0 truncate font-medium text-indigo-700">📄 {{ doc.original_name }}</span>
                                        <span class="shrink-0 text-xs font-bold text-gray-500">{{ isHindi ? 'खोलें' : 'Open' }} ↗</span>
                                    </a>
                                </li>
                            </ul>
                        </section>

                        <HelpDonationBox :help-request="helpRequest" class="mt-6" />

                        <div class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                            <p class="font-semibold text-gray-900 text-sm">
                                {{ isHindi ? 'अनुरोधकर्ता:' : 'Requested by:' }}
                                <span class="text-green-700">{{ helpRequest.name }}</span>
                            </p>
                            <span class="text-xs font-medium text-gray-500">{{ new Date(helpRequest.created_at).toLocaleDateString() }}</span>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <a :href="`tel:${helpRequest.mobile}`" class="flex-1 text-center bg-gray-50 border border-gray-200 text-gray-700 hover:bg-green-50 hover:text-green-700 hover:border-green-200 text-sm font-bold py-2.5 px-4 rounded-lg transition-colors">
                                📞 {{ isHindi ? 'कॉल करें' : 'Call' }}
                            </a>
                            <a v-if="whatsappNumber" :href="`https://wa.me/${whatsappNumber}`" target="_blank" rel="noopener noreferrer" class="flex-1 text-center bg-green-500 text-white hover:bg-green-600 text-sm font-bold py-2.5 px-4 rounded-lg shadow-xs transition-colors">
                                {{ isHindi ? 'WhatsApp पर संपर्क' : 'WhatsApp' }}
                            </a>
                        </div>

                        <HelpShareButtons :help-request="helpRequest" class="mt-8 pt-6 border-t border-gray-100" />
                    </div>
                </article>
            </div>
        </div>
    </component>
</template>
