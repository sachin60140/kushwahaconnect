<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    socialHref: {
        type: String,
        default: '#social-connect',
    },
});

const { locale, __ } = useLanguage();

const intro = computed(() => {
    const key = locale.value === 'hi' ? 'hero_intro_hi' : 'hero_intro_en';
    return props.settings[key] || __('hero_desc');
});
</script>

<template>
    <section class="relative overflow-hidden bg-blue-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(249,115,22,0.28),_transparent_32%),radial-gradient(circle_at_bottom_right,_rgba(21,128,61,0.22),_transparent_34%)]"></div>
        <div class="absolute left-1/2 top-10 h-72 w-72 -translate-x-1/2 rounded-full bg-white/10 blur-3xl"></div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8 lg:py-24">
            <div class="text-center lg:text-left">
                <div class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-bold text-orange-100 shadow-sm backdrop-blur">
                    {{ __('hero_badge') }}
                </div>

                <h1 class="mt-7 text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                    <span class="block text-orange-300">{{ __('hero_hindi_heading') }}</span>
                    <span class="mt-4 block text-2xl font-extrabold leading-snug text-blue-100 sm:text-3xl lg:text-4xl">
                        {{ __('hero_english_heading') }}
                    </span>
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-8 text-blue-100 lg:mx-0">
                    {{ intro }}
                </p>

                <div class="mt-9 grid gap-3 sm:flex sm:justify-center lg:justify-start">
                    <Link :href="route('join')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-orange-500 px-7 text-base font-black text-white shadow-lg shadow-orange-950/20 transition hover:-translate-y-0.5 hover:bg-orange-600">
                        {{ __('btn_join') }}
                    </Link>
                    <Link :href="route('matrimonial')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-green-700 px-7 text-base font-black text-white shadow-lg shadow-green-950/20 transition hover:-translate-y-0.5 hover:bg-green-800">
                        {{ __('btn_matrimonial') }}
                    </Link>
                    <a :href="socialHref" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 text-base font-black text-white transition hover:-translate-y-0.5 hover:bg-white/15">
                        {{ __('hero_social_link') }}
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-lg">
                <div class="absolute -left-6 top-8 h-24 w-24 rounded-full bg-orange-400/25 blur-2xl"></div>
                <div class="absolute -right-6 bottom-4 h-32 w-32 rounded-full bg-green-400/20 blur-2xl"></div>

                <div class="relative rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl backdrop-blur">
                    <div class="rounded-[1.5rem] bg-white p-5 text-slate-900">
                        <div class="flex items-center justify-between rounded-2xl bg-orange-50 px-4 py-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-orange-600">KushwahaConnect</p>
                                <p class="mt-1 text-sm font-bold text-blue-950">{{ __('hero_tagline') }}</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-sm font-black text-orange-300">KC</span>
                        </div>

                        <div class="mt-5 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-blue-50 p-4">
                                <div class="flex -space-x-2">
                                    <span v-for="item in 4" :key="item" class="h-9 w-9 rounded-full border-2 border-white bg-gradient-to-br from-orange-300 to-green-500"></span>
                                </div>
                                <p class="mt-4 text-sm font-black text-blue-950">{{ __('why_connections_title') }}</p>
                            </div>
                            <div class="rounded-2xl bg-green-50 p-4">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-700 text-lg font-black text-white">+</span>
                                <p class="mt-4 text-sm font-black text-green-900">{{ __('help_title') }}</p>
                            </div>
                            <div class="rounded-2xl bg-orange-50 p-4">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-orange-500 text-lg font-black text-white">✓</span>
                                <p class="mt-4 text-sm font-black text-orange-900">{{ __('verified') }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-950 text-lg font-black text-white">♥</span>
                                <p class="mt-4 text-sm font-black text-blue-950">{{ __('matrimonial_title') }}</p>
                            </div>
                        </div>

                        <div class="mt-5 rounded-2xl border border-slate-100 p-4">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-900 to-green-700"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="h-3 w-4/5 rounded-full bg-slate-200"></div>
                                    <div class="mt-2 h-3 w-2/3 rounded-full bg-slate-100"></div>
                                </div>
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-800">{{ __('btn_connect') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
