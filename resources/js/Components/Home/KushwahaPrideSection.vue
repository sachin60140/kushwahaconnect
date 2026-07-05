<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    highlight: {
        type: Object,
        default: () => ({ featured: null, cards: [] }),
    },
});

const { locale } = useLanguage();
const isHindi = computed(() => locale.value === 'hi');
const featured = computed(() => props.highlight?.featured || null);
const cards = computed(() => props.highlight?.cards || []);
const hasProfiles = computed(() => featured.value || cards.value.length > 0);

const text = computed(() => ({
    eyebrow: isHindi.value ? 'कुशवाहा गौरव' : 'Kushwaha Pride',
    title: isHindi.value ? 'समाज के प्रेरक व्यक्तित्व' : 'Inspiring Community Personalities',
    desc: isHindi.value
        ? 'शिक्षा, सेवा, विज्ञान, व्यवसाय, खेल और समाजसेवा में योगदान देने वाले सत्यापित व्यक्तित्व।'
        : 'Verified personalities contributing in education, service, science, business, sports, and social work.',
    verified: isHindi.value ? 'सत्यापित' : 'Verified',
    lastVerified: isHindi.value ? 'अंतिम सत्यापन' : 'Last verified',
    viewAll: isHindi.value ? 'सभी देखें' : 'View All',
    nominate: isHindi.value ? 'नाम सुझाएँ' : 'Nominate',
}));

const name = (profile) => isHindi.value ? profile.name_hi : profile.name_en;
const designation = (profile) => isHindi.value ? profile.designation_hi || profile.designation_en : profile.designation_en || profile.designation_hi;
</script>

<template>
    <section v-if="hasProfiles" class="bg-white px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-600">{{ text.eyebrow }}</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ text.title }}</h2>
                    <p class="mt-4 max-w-3xl text-lg leading-8 text-slate-600">{{ text.desc }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('pride.index')" class="kc-btn-blue">{{ text.viewAll }}</Link>
                    <Link :href="route('nominations.create')" class="kc-btn-secondary">{{ text.nominate }}</Link>
                </div>
            </div>

            <div class="mt-10 grid gap-6 lg:grid-cols-[1.1fr_1fr]">
                <article v-if="featured" class="overflow-hidden rounded-3xl bg-blue-950 text-white shadow-xl shadow-blue-950/15">
                    <div class="grid min-h-full md:grid-cols-[0.9fr_1.1fr]">
                        <div class="h-80 bg-blue-900 md:h-full">
                            <img v-if="featured.photo_url" :src="featured.photo_url" :alt="name(featured)" class="h-full w-full object-cover">
                            <div v-else class="flex h-full items-center justify-center text-5xl font-black text-white/20">KC</div>
                        </div>
                        <div class="p-7">
                            <span class="rounded-full bg-orange-400 px-3 py-1 text-xs font-black text-blue-950">{{ featured.category }}</span>
                            <h3 class="mt-5 text-3xl font-black">{{ name(featured) }}</h3>
                            <p class="mt-2 text-base font-bold text-orange-100">{{ designation(featured) || featured.neutral_label }}</p>
                            <p class="mt-3 text-sm text-blue-100">{{ [featured.city, featured.district, featured.state].filter(Boolean).join(', ') }}</p>
                            <p class="mt-5 line-clamp-5 text-sm leading-7 text-blue-100">
                                {{ isHindi ? featured.biography_hi || featured.biography_en : featured.biography_en || featured.biography_hi }}
                            </p>
                            <p v-if="featured.last_verified_at" class="mt-5 text-xs font-black text-green-200">
                                {{ text.lastVerified }}: {{ new Date(featured.last_verified_at).toLocaleDateString() }}
                            </p>
                        </div>
                    </div>
                </article>

                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="profile in cards" :key="profile.id" class="rounded-3xl border border-slate-100 bg-[#fffaf0] p-5 shadow-sm">
                        <div class="flex gap-4">
                            <div class="h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-blue-100">
                                <img v-if="profile.photo_url" :src="profile.photo_url" :alt="name(profile)" class="h-full w-full object-cover">
                            </div>
                            <div>
                                <span class="rounded-full bg-green-100 px-3 py-1 text-[11px] font-black text-green-700">{{ text.verified }}</span>
                                <h3 class="mt-3 text-lg font-black leading-tight text-blue-950">{{ name(profile) }}</h3>
                                <p class="mt-1 text-sm font-bold text-slate-600">{{ designation(profile) || profile.category }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ profile.last_verified_at ? `${text.lastVerified}: ${new Date(profile.last_verified_at).toLocaleDateString()}` : profile.state }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
