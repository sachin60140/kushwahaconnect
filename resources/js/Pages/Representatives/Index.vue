<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import Modal from '@/Components/Modal.vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    representatives: Object,
    filters: Object,
    categories: Array,
    statuses: Array,
    states: Array,
    districts: Array,
    parties: Array,
    constituencies: Array,
});

const { locale } = useLanguage();
const isHindi = computed(() => locale.value === 'hi');
const selectedRepresentative = ref(null);
const correctionTarget = ref(null);

const text = computed(() => ({
    title: isHindi.value ? 'प्रमुख जनप्रतिनिधि' : 'Prominent Public Representatives',
    eyebrow: isHindi.value ? 'तटस्थ और सत्यापित जानकारी' : 'Neutral and Verified Public Information',
    intro: isHindi.value
        ? 'कुशवाहा समाज से जुड़े वर्तमान और पूर्व जनप्रतिनिधियों की सत्यापित सार्वजनिक प्रोफाइल।'
        : 'Verified public profiles of current and former representatives from the Kushwaha community.',
    search: isHindi.value ? 'नाम, दल या क्षेत्र खोजें' : 'Search name, party or constituency',
    allStatuses: isHindi.value ? 'वर्तमान / पूर्व' : 'Current / Former',
    allCategories: isHindi.value ? 'सभी श्रेणियां' : 'All Categories',
    allStates: isHindi.value ? 'सभी राज्य' : 'All States',
    allDistricts: isHindi.value ? 'सभी जिले' : 'All Districts',
    allParties: isHindi.value ? 'सभी दल' : 'All Parties',
    allConstituencies: isHindi.value ? 'सभी क्षेत्र' : 'All Constituencies',
    termYear: isHindi.value ? 'कार्यकाल वर्ष' : 'Term year',
    filter: isHindi.value ? 'फिल्टर करें' : 'Filter',
    clear: isHindi.value ? 'रीसेट' : 'Clear',
    verified: isHindi.value ? 'सत्यापित' : 'Verified',
    current: isHindi.value ? 'वर्तमान प्रतिनिधि' : 'Current Representative',
    former: isHindi.value ? 'पूर्व प्रतिनिधि' : 'Former Representative',
    lastVerified: isHindi.value ? 'अंतिम सत्यापन' : 'Last verified',
    readMore: isHindi.value ? 'पूरा विवरण' : 'View Details',
    report: isHindi.value ? 'गलत जानकारी रिपोर्ट करें' : 'Report Incorrect Information',
    sources: isHindi.value ? 'स्रोत' : 'Sources',
    empty: isHindi.value ? 'अभी कोई प्रकाशित जनप्रतिनिधि प्रोफाइल उपलब्ध नहीं है।' : 'No published representative profiles are available yet.',
    submitCorrection: isHindi.value ? 'सुधार अनुरोध भेजें' : 'Submit Correction',
}));

const filterForm = useForm({
    search: props.filters?.search || '',
    representative_status: props.filters?.representative_status || '',
    category: props.filters?.category || '',
    state: props.filters?.state || '',
    district: props.filters?.district || '',
    political_party: props.filters?.political_party || '',
    constituency: props.filters?.constituency || '',
    term_year: props.filters?.term_year || '',
});

const correctionForm = useForm({
    profile_type: 'representative',
    profile_id: '',
    profile_name: '',
    requester_name: '',
    requester_mobile: '',
    requester_email: '',
    correction_details: '',
    source_links: '',
});

const bio = (representative) => isHindi.value ? representative.biography_hi || representative.biography_en : representative.biography_en || representative.biography_hi;
const statusLabel = (status) => status === 'current' ? text.value.current : text.value.former;

const submitFilters = () => {
    filterForm.get(route('representatives.index'), {
        preserveState: true,
        replace: true,
    });
};

const openCorrection = (representative) => {
    correctionTarget.value = representative;
    correctionForm.profile_id = representative.id;
    correctionForm.profile_name = representative.name;
};

const submitCorrection = () => {
    correctionForm.post(route('corrections.store'), {
        preserveScroll: true,
        onSuccess: () => {
            correctionForm.reset('requester_name', 'requester_mobile', 'requester_email', 'correction_details', 'source_links');
            correctionTarget.value = null;
        },
    });
};
</script>

<template>
    <SeoMeta
        title="Prominent Public Representatives"
        description="Verified public profiles of current and former representatives from the Kushwaha community."
    />

    <PublicLayout>
        <section class="kc-hero">
            <div class="kc-hero-inner">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ text.eyebrow }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ text.title }}</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-blue-100">{{ text.intro }}</p>
            </div>
        </section>

        <section class="kc-page">
            <div class="kc-container">
                <form @submit.prevent="submitFilters" class="kc-surface mb-8 grid gap-4 p-4 md:grid-cols-4">
                    <input v-model="filterForm.search" type="search" :placeholder="text.search">
                    <select v-model="filterForm.representative_status">
                        <option value="">{{ text.allStatuses }}</option>
                        <option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option>
                    </select>
                    <select v-model="filterForm.category">
                        <option value="">{{ text.allCategories }}</option>
                        <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                    </select>
                    <select v-model="filterForm.state">
                        <option value="">{{ text.allStates }}</option>
                        <option v-for="state in states" :key="state" :value="state">{{ state }}</option>
                    </select>
                    <select v-model="filterForm.district">
                        <option value="">{{ text.allDistricts }}</option>
                        <option v-for="district in districts" :key="district" :value="district">{{ district }}</option>
                    </select>
                    <select v-model="filterForm.political_party">
                        <option value="">{{ text.allParties }}</option>
                        <option v-for="party in parties" :key="party" :value="party">{{ party }}</option>
                    </select>
                    <select v-model="filterForm.constituency">
                        <option value="">{{ text.allConstituencies }}</option>
                        <option v-for="constituency in constituencies" :key="constituency" :value="constituency">{{ constituency }}</option>
                    </select>
                    <input v-model="filterForm.term_year" type="number" min="1950" max="2100" :placeholder="text.termYear">
                    <div class="flex gap-3 md:col-span-4">
                        <button type="submit" class="kc-btn-primary">{{ text.filter }}</button>
                        <Link :href="route('representatives.index')" class="kc-btn-secondary">{{ text.clear }}</Link>
                    </div>
                </form>

                <div v-if="representatives.data.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="representative in representatives.data"
                        :key="representative.id"
                        class="group overflow-hidden rounded-[1.75rem] border border-blue-950/10 bg-white shadow-sm shadow-blue-950/5 transition duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-blue-950/10"
                    >
                        <div class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-green-800 p-5 text-white">
                            <div class="absolute -right-12 -top-16 h-40 w-40 rounded-full bg-white/10"></div>
                            <div class="absolute -bottom-20 left-8 h-44 w-44 rounded-full bg-orange-300/10"></div>
                            <div class="relative flex items-start gap-4">
                                <div class="flex h-36 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/35 bg-white p-1 shadow-xl shadow-blue-950/30">
                                    <img v-if="representative.photo_url" :src="representative.photo_url" :alt="representative.name" class="h-full w-full rounded-xl object-contain object-center" loading="lazy">
                                    <div v-else class="flex h-full w-full items-center justify-center rounded-xl bg-blue-50 text-2xl font-black text-blue-950/25">KC</div>
                                </div>
                                <div class="min-w-0 flex-1 pt-1">
                                    <div class="flex flex-wrap gap-2">
                                        <span class="rounded-full bg-white/15 px-3 py-1 text-[11px] font-black text-white ring-1 ring-white/25">{{ statusLabel(representative.representative_status) }}</span>
                                        <span v-if="representative.is_verified" class="rounded-full bg-green-200 px-3 py-1 text-[11px] font-black text-green-950">{{ text.verified }}</span>
                                    </div>
                                    <h2 class="mt-4 text-2xl font-black leading-tight tracking-tight">{{ representative.name }}</h2>
                                    <p class="mt-2 text-sm font-bold leading-6 text-blue-100">{{ representative.current_designation || representative.category }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-3 text-sm">
                                <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Area</p>
                                    <p class="mt-1 font-bold text-blue-950">{{ [representative.constituency, representative.district, representative.state].filter(Boolean).join(', ') || 'N/A' }}</p>
                                </div>
                                <div v-if="representative.political_party" class="rounded-2xl bg-orange-50 px-4 py-3">
                                    <p class="text-[11px] font-black uppercase tracking-[0.16em] text-orange-500">Party</p>
                                    <p class="mt-1 font-black text-orange-800">{{ representative.political_party }}</p>
                                </div>
                            </div>

                            <p v-if="bio(representative)" class="line-clamp-3 text-sm leading-6 text-slate-600">{{ bio(representative) }}</p>
                            <p v-if="representative.last_verified_at" class="rounded-full bg-green-50 px-3 py-2 text-xs font-bold text-green-700">{{ text.lastVerified }}: {{ new Date(representative.last_verified_at).toLocaleDateString() }}</p>
                            <div class="grid gap-3 sm:grid-cols-[0.9fr_1.1fr]">
                                <button @click="selectedRepresentative = representative" type="button" class="kc-btn-blue w-full justify-center px-4">{{ text.readMore }}</button>
                                <button @click="openCorrection(representative)" type="button" class="kc-btn-secondary w-full justify-center px-4">{{ text.report }}</button>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="kc-surface p-10 text-center text-lg font-bold text-slate-600">{{ text.empty }}</div>

                <div v-if="representatives.links && representatives.links.length > 3" class="mt-10 flex justify-center">
                    <div class="flex flex-wrap shadow-sm rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in representatives.links" :key="p">
                            <div v-if="link.url === null" class="px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="px-4 py-3 text-sm leading-4 hover:bg-gray-50" :class="{ 'bg-green-50 font-bold text-green-700': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <Modal :show="!!selectedRepresentative" @close="selectedRepresentative = null" maxWidth="3xl">
            <div v-if="selectedRepresentative" class="bg-white p-8">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-orange-600">{{ selectedRepresentative.category }}</p>
                <h2 class="mt-2 text-3xl font-black text-blue-950">{{ selectedRepresentative.name }}</h2>
                <p class="mt-2 font-bold text-slate-600">{{ selectedRepresentative.current_designation }}</p>
                <p class="mt-6 whitespace-pre-wrap leading-8 text-slate-700">{{ bio(selectedRepresentative) }}</p>
                <div class="mt-8 grid gap-4 text-sm md:grid-cols-2">
                    <p><strong>Party:</strong> {{ selectedRepresentative.political_party || 'N/A' }}</p>
                    <p><strong>House/Public body:</strong> {{ selectedRepresentative.house_or_body || 'N/A' }}</p>
                    <p><strong>Term start:</strong> {{ selectedRepresentative.term_start_date ? new Date(selectedRepresentative.term_start_date).toLocaleDateString() : 'N/A' }}</p>
                    <p><strong>Term end:</strong> {{ selectedRepresentative.term_end_date ? new Date(selectedRepresentative.term_end_date).toLocaleDateString() : 'N/A' }}</p>
                </div>
                <h3 class="mt-8 text-xl font-black text-blue-950">{{ text.sources }}</h3>
                <div class="mt-3 space-y-2 text-sm font-bold text-orange-700">
                    <a v-if="selectedRepresentative.government_source_url" :href="selectedRepresentative.government_source_url" target="_blank">Government Source</a>
                    <a v-if="selectedRepresentative.election_source_url" :href="selectedRepresentative.election_source_url" target="_blank">Election Source</a>
                </div>
                <p v-if="selectedRepresentative.verification_note" class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{{ selectedRepresentative.verification_note }}</p>
            </div>
        </Modal>

        <Modal :show="!!correctionTarget" @close="correctionTarget = null" maxWidth="2xl">
            <form v-if="correctionTarget" @submit.prevent="submitCorrection" class="space-y-5 bg-white p-8">
                <h2 class="text-2xl font-black text-blue-950">{{ text.report }}</h2>
                <p class="text-sm font-bold text-slate-600">{{ correctionForm.profile_name }}</p>
                <input v-model="correctionForm.requester_name" type="text" placeholder="Your name" required>
                <input v-model="correctionForm.requester_mobile" type="text" placeholder="Mobile">
                <input v-model="correctionForm.requester_email" type="email" placeholder="Email">
                <textarea v-model="correctionForm.correction_details" rows="5" placeholder="Correction details" required></textarea>
                <textarea v-model="correctionForm.source_links" rows="3" placeholder="Source links"></textarea>
                <button type="submit" :disabled="correctionForm.processing" class="kc-btn-primary">{{ text.submitCorrection }}</button>
            </form>
        </Modal>
    </PublicLayout>
</template>
