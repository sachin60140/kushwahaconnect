<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    representatives: {
        type: Array,
        default: () => [],
    },
});

const { locale } = useLanguage();
const isHindi = computed(() => locale.value === 'hi');

const text = computed(() => ({
    eyebrow: isHindi.value ? 'प्रमुख जनप्रतिनिधि' : 'Prominent Public Representatives',
    title: isHindi.value ? 'सत्यापित वर्तमान जनप्रतिनिधि' : 'Selected Current Public Representatives',
    desc: isHindi.value
        ? 'समान और तटस्थ प्रोफाइल लेआउट के साथ सार्वजनिक स्रोतों पर आधारित जानकारी।'
        : 'Public-source based information shown with a consistent and politically neutral profile layout.',
    verified: isHindi.value ? 'सत्यापित' : 'Verified',
    current: isHindi.value ? 'वर्तमान प्रतिनिधि' : 'Current Representative',
    lastVerified: isHindi.value ? 'अंतिम सत्यापन' : 'Last verified',
    viewAll: isHindi.value ? 'सभी देखें' : 'View All',
}));
</script>

<template>
    <section v-if="representatives.length" class="bg-[#fffaf0] px-4 py-14 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-green-700">{{ text.eyebrow }}</p>
                    <h2 class="mt-3 text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ text.title }}</h2>
                    <p class="mt-4 max-w-3xl text-lg leading-8 text-slate-600">{{ text.desc }}</p>
                </div>
                <Link :href="route('representatives.index')" class="kc-btn-green">{{ text.viewAll }}</Link>
            </div>

            <div class="mt-10 grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="representative in representatives"
                    :key="representative.id"
                    class="group overflow-hidden rounded-[1.5rem] border border-blue-950/10 bg-white shadow-sm shadow-blue-950/5 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10"
                >
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-green-800 p-4 text-white">
                        <div class="absolute -right-12 -top-14 h-32 w-32 rounded-full bg-white/10"></div>
                        <div class="relative flex items-start gap-4">
                            <div class="flex h-32 w-24 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/35 bg-white p-1 shadow-lg shadow-blue-950/30">
                                <img v-if="representative.photo_url" :src="representative.photo_url" :alt="representative.name" class="h-full w-full rounded-xl object-contain object-center" loading="lazy">
                                <div v-else class="flex h-full w-full items-center justify-center rounded-xl bg-blue-50 text-xl font-black text-blue-950/25">KC</div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-white/15 px-3 py-1 text-[10px] font-black text-white ring-1 ring-white/25">{{ text.current }}</span>
                                    <span v-if="representative.is_verified" class="rounded-full bg-green-200 px-3 py-1 text-[10px] font-black text-green-950">{{ text.verified }}</span>
                                </div>
                                <h3 class="mt-4 text-xl font-black leading-tight tracking-tight">{{ representative.name }}</h3>
                                <p class="mt-2 text-sm font-bold leading-6 text-blue-100">{{ representative.current_designation || representative.category }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-400">Area</p>
                            <p class="mt-1 text-sm font-bold text-blue-950">{{ [representative.constituency, representative.state].filter(Boolean).join(', ') || 'N/A' }}</p>
                        </div>
                        <p v-if="representative.last_verified_at" class="rounded-full bg-green-50 px-3 py-2 text-xs font-black text-green-700">
                            {{ text.lastVerified }}: {{ new Date(representative.last_verified_at).toLocaleDateString() }}
                        </p>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>
