<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import Modal from '@/Components/Modal.vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    profiles: Object,
    filters: Object,
    categories: Array,
    states: Array,
    districts: Array,
});

const { locale } = useLanguage();
const isHindi = computed(() => locale.value === 'hi');
const selectedProfile = ref(null);
const correctionTarget = ref(null);

const text = computed(() => ({
    title: isHindi.value ? 'कुशवाहा गौरव' : 'Kushwaha Pride',
    eyebrow: isHindi.value ? 'सत्यापित प्रेरक व्यक्तित्व' : 'Verified Community Personalities',
    intro: isHindi.value
        ? 'देशभर में कुशवाहा समाज से जुड़े शिक्षा, सेवा, व्यवसाय, विज्ञान, खेल और समाजसेवा के प्रेरक व्यक्तित्व।'
        : 'Verified inspiring personalities from the Kushwaha community across education, service, business, science, sports, and social work.',
    search: isHindi.value ? 'नाम, पद या संस्था खोजें' : 'Search name, designation or organisation',
    allCategories: isHindi.value ? 'सभी श्रेणियां' : 'All Categories',
    allStates: isHindi.value ? 'सभी राज्य' : 'All States',
    allDistricts: isHindi.value ? 'सभी जिले' : 'All Districts',
    filter: isHindi.value ? 'फिल्टर करें' : 'Filter',
    clear: isHindi.value ? 'रीसेट' : 'Clear',
    verified: isHindi.value ? 'सत्यापित' : 'Verified',
    lastVerified: isHindi.value ? 'अंतिम सत्यापन' : 'Last verified',
    sources: isHindi.value ? 'सत्यापन स्रोत' : 'Verification sources',
    achievements: isHindi.value ? 'उपलब्धियां' : 'Achievements',
    readMore: isHindi.value ? 'पूरा विवरण' : 'View Details',
    report: isHindi.value ? 'गलत जानकारी रिपोर्ट करें' : 'Report Incorrect Information',
    nominate: isHindi.value ? 'किसी प्रेरक व्यक्ति का नाम सुझाएँ' : 'Nominate an Inspiring Person',
    empty: isHindi.value ? 'अभी कोई प्रकाशित प्रोफाइल उपलब्ध नहीं है।' : 'No published profiles are available yet.',
    submitCorrection: isHindi.value ? 'सुधार अनुरोध भेजें' : 'Submit Correction',
}));

const filterForm = useForm({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    state: props.filters?.state || '',
    district: props.filters?.district || '',
});

const correctionForm = useForm({
    profile_type: 'personality',
    profile_id: '',
    profile_name: '',
    requester_name: '',
    requester_mobile: '',
    requester_email: '',
    correction_details: '',
    source_links: '',
});

const localizedName = (profile) => isHindi.value ? profile.name_hi : profile.name_en;
const localizedDesignation = (profile) => isHindi.value ? profile.designation_hi || profile.designation_en : profile.designation_en || profile.designation_hi;
const localizedBio = (profile) => isHindi.value ? profile.biography_hi || profile.biography_en : profile.biography_en || profile.biography_hi;
const localizedAchievements = (profile) => isHindi.value ? profile.achievements_hi || profile.achievements_en : profile.achievements_en || profile.achievements_hi;

const submitFilters = () => {
    filterForm.get(route('pride.index'), {
        preserveState: true,
        replace: true,
    });
};

const openProfile = (profile) => {
    selectedProfile.value = profile;
};

const openCorrection = (profile) => {
    correctionTarget.value = profile;
    correctionForm.profile_id = profile.id;
    correctionForm.profile_name = localizedName(profile);
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
        title="Kushwaha Pride"
        description="Verified inspiring personalities from the Kushwaha community across India."
    />

    <PublicLayout>
        <section class="kc-hero">
            <div class="kc-hero-inner">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ text.eyebrow }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ text.title }}</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-blue-100">{{ text.intro }}</p>
                <Link :href="route('nominations.create')" class="mt-8 inline-flex min-h-12 items-center justify-center rounded-full bg-orange-500 px-6 text-sm font-black text-white hover:bg-orange-600">
                    {{ text.nominate }}
                </Link>
            </div>
        </section>

        <section class="kc-page">
            <div class="kc-container">
                <form @submit.prevent="submitFilters" class="kc-surface mb-8 grid gap-4 p-4 md:grid-cols-5">
                    <input v-model="filterForm.search" type="search" :placeholder="text.search" class="md:col-span-2">
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
                    <div class="flex gap-3 md:col-span-5">
                        <button type="submit" class="kc-btn-primary">{{ text.filter }}</button>
                        <Link :href="route('pride.index')" class="kc-btn-secondary">{{ text.clear }}</Link>
                    </div>
                </form>

                <div v-if="profiles.data.length" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <article v-for="profile in profiles.data" :key="profile.id" class="kc-panel overflow-hidden">
                        <div class="h-56 bg-linear-to-br from-blue-100 to-green-50">
                            <img v-if="profile.photo_url" :src="profile.photo_url" :alt="localizedName(profile)" class="h-full w-full object-cover">
                            <div v-else class="flex h-full items-center justify-center text-4xl font-black text-blue-950/20">KC</div>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">{{ profile.category }}</span>
                                <span v-if="profile.is_verified" class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-700">{{ text.verified }}</span>
                            </div>
                            <h2 class="mt-4 text-2xl font-black text-blue-950">{{ localizedName(profile) }}</h2>
                            <p class="mt-1 text-sm font-bold text-slate-600">{{ localizedDesignation(profile) || profile.neutral_label }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ [profile.city, profile.district, profile.state].filter(Boolean).join(', ') }}</p>
                            <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600">{{ localizedBio(profile) }}</p>
                            <p v-if="profile.last_verified_at" class="mt-4 text-xs font-bold text-green-700">{{ text.lastVerified }}: {{ new Date(profile.last_verified_at).toLocaleDateString() }}</p>
                            <div class="mt-5 flex flex-wrap gap-3">
                                <button @click="openProfile(profile)" type="button" class="kc-btn-blue">{{ text.readMore }}</button>
                                <button @click="openCorrection(profile)" type="button" class="kc-btn-secondary">{{ text.report }}</button>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="kc-surface p-10 text-center text-lg font-bold text-slate-600">{{ text.empty }}</div>

                <div v-if="profiles.links && profiles.links.length > 3" class="mt-10 flex justify-center">
                    <div class="flex flex-wrap shadow-xs rounded-lg bg-white overflow-hidden border border-gray-200">
                        <template v-for="(link, p) in profiles.links" :key="p">
                            <div v-if="link.url === null" class="px-4 py-3 text-sm leading-4 text-gray-400" v-html="link.label" />
                            <Link v-else class="px-4 py-3 text-sm leading-4 hover:bg-gray-50" :class="{ 'bg-orange-50 font-bold text-orange-700': link.active }" :href="link.url" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <Modal :show="!!selectedProfile" @close="selectedProfile = null" maxWidth="3xl">
            <div v-if="selectedProfile" class="bg-white p-8">
                <p class="text-sm font-black uppercase tracking-[0.2em] text-orange-600">{{ selectedProfile.category }}</p>
                <h2 class="mt-2 text-3xl font-black text-blue-950">{{ localizedName(selectedProfile) }}</h2>
                <p class="mt-2 font-bold text-slate-600">{{ localizedDesignation(selectedProfile) }}</p>
                <p class="mt-6 whitespace-pre-wrap leading-8 text-slate-700">{{ localizedBio(selectedProfile) }}</p>
                <h3 class="mt-8 text-xl font-black text-green-800">{{ text.achievements }}</h3>
                <p class="mt-3 whitespace-pre-wrap leading-7 text-slate-700">{{ localizedAchievements(selectedProfile) }}</p>
                <h3 v-if="selectedProfile.verification_sources" class="mt-8 text-xl font-black text-blue-950">{{ text.sources }}</h3>
                <p v-if="selectedProfile.verification_sources" class="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-600">{{ selectedProfile.verification_sources }}</p>
            </div>
        </Modal>

        <Modal :show="!!correctionTarget" @close="correctionTarget = null" maxWidth="2xl">
            <form v-if="correctionTarget" @submit.prevent="submitCorrection" class="space-y-5 bg-white p-8">
                <h2 class="text-2xl font-black text-blue-950">{{ text.report }}</h2>
                <p class="text-sm font-bold text-slate-600">{{ correctionForm.profile_name }}</p>
                <input v-model="correctionForm.requester_name" type="text" placeholder="Your name" required>
                <div>
                    <input v-model="correctionForm.requester_mobile" type="tel" inputmode="numeric" autocomplete="tel" placeholder="Mobile *" required class="w-full">
                    <MobileOtpField :mobile="correctionForm.requester_mobile" :error="correctionForm.errors.requester_mobile" id-prefix="correction-mobile-otp" />
                </div>
                <input v-model="correctionForm.requester_email" type="email" placeholder="Email">
                <textarea v-model="correctionForm.correction_details" rows="5" placeholder="Correction details" required></textarea>
                <textarea v-model="correctionForm.source_links" rows="3" placeholder="Source links"></textarea>
                <button type="submit" :disabled="correctionForm.processing" class="kc-btn-primary">{{ text.submitCorrection }}</button>
            </form>
        </Modal>
    </PublicLayout>
</template>
