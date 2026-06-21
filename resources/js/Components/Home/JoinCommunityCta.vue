<script setup>
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();

const benefits = ['join_benefit_1', 'join_benefit_2', 'join_benefit_3'];
</script>

<template>
    <section class="bg-[#fffaf0] px-4 py-12 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-blue-950 shadow-2xl shadow-blue-950/15">
            <div class="grid items-center gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:p-10">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-white sm:text-4xl">{{ __('join_cta_title') }}</h2>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-blue-100">{{ __('join_cta_desc') }}</p>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div v-for="benefit in benefits" :key="benefit" class="rounded-2xl bg-white/10 p-4 text-sm font-bold text-white">
                            {{ __(benefit) }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-3 sm:min-w-56">
                    <Link :href="route('join')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-orange-500 px-7 text-base font-black text-white shadow-lg transition hover:bg-orange-600">
                        {{ __('btn_create_profile') }}
                    </Link>
                    <Link :href="route('login')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-white px-7 text-base font-black text-blue-950 transition hover:bg-orange-50">
                        {{ __('btn_login') }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
