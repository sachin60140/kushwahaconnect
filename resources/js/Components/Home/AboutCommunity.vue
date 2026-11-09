<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const { locale, __ } = useLanguage();

const text = (key, fallback) => {
    const localizedKey = `${key}_${locale.value}`;
    return props.settings[localizedKey] || fallback;
};

const aboutImage = computed(() => props.settings.about_image_url || '');
</script>

<template>
    <section class="bg-[#fffaf0] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2">
            <div class="relative overflow-hidden rounded-3xl bg-blue-950 shadow-2xl shadow-blue-950/15">
                <img
                    v-if="aboutImage"
                    :src="aboutImage"
                    alt="Kushwaha Samaj community"
                    class="h-full min-h-[360px] w-full object-cover"
                    loading="lazy"
                >
                <div v-else class="min-h-[360px] bg-[radial-gradient(circle_at_25%_20%,rgba(249,115,22,0.45),transparent_28%),linear-gradient(135deg,#071d49,#166534)] p-8 text-white">
                    <div class="grid h-full grid-cols-2 gap-4">
                        <div class="rounded-3xl bg-white/15 p-5 backdrop-blur-sm">
                            <p class="text-sm font-bold text-orange-100">{{ __('why_education_title') }}</p>
                            <div class="mt-8 h-16 rounded-2xl bg-white/20"></div>
                        </div>
                        <div class="mt-12 rounded-3xl bg-white/15 p-5 backdrop-blur-sm">
                            <p class="text-sm font-bold text-green-100">{{ __('why_business_title') }}</p>
                            <div class="mt-8 h-16 rounded-2xl bg-white/20"></div>
                        </div>
                        <div class="rounded-3xl bg-white/15 p-5 backdrop-blur-sm">
                            <p class="text-sm font-bold text-blue-100">{{ __('why_connections_title') }}</p>
                            <div class="mt-8 h-16 rounded-2xl bg-white/20"></div>
                        </div>
                        <div class="mt-12 rounded-3xl bg-white/15 p-5 backdrop-blur-sm">
                            <p class="text-sm font-bold text-orange-100">{{ __('why_help_title') }}</p>
                            <div class="mt-8 h-16 rounded-2xl bg-white/20"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-600">Kushwaha Samaj</p>
                <h2 class="mt-3 text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">
                    {{ __('about_title') }}
                </h2>
                <p class="mt-6 text-lg leading-8 text-slate-700">
                    {{ text('about_intro', __('about_p1')) }}
                </p>
                <p class="mt-5 text-lg leading-8 text-slate-700">
                    {{ text('about_body', __('about_p2')) }}
                </p>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5">
                        <h3 class="text-lg font-black text-orange-700">{{ __('mission_title') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ __('mission_text') }}</p>
                    </div>
                    <div class="rounded-2xl border border-green-100 bg-green-50 p-5">
                        <h3 class="text-lg font-black text-green-800">{{ __('vision_title') }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-700">{{ __('vision_text') }}</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:flex">
                    <Link href="/about" class="inline-flex min-h-12 items-center justify-center rounded-full border border-blue-950/15 px-6 text-sm font-black text-blue-950 transition hover:border-blue-950 hover:bg-blue-50">
                        {{ __('btn_learn_more') }}
                    </Link>
                    <Link :href="route('join')" class="inline-flex min-h-12 items-center justify-center rounded-full bg-orange-500 px-6 text-sm font-black text-white shadow-xs transition hover:bg-orange-600">
                        {{ __('btn_join') }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
