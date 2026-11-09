<script setup>
import { Link } from '@inertiajs/vue3';
import AppVersionFooter from '@/Components/AppVersionFooter.vue';
</script>

<template>
    <div class="min-h-screen bg-[#fffaf0] text-slate-900">
        <div class="grid min-h-screen lg:grid-cols-[1fr_0.9fr]">
            <section class="relative hidden overflow-hidden bg-blue-950 px-12 py-16 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.28),transparent_32%),radial-gradient(circle_at_bottom_right,rgba(21,128,61,0.24),transparent_34%)]"></div>
                <div class="relative">
                    <Link :href="route('welcome')" class="inline-flex items-center gap-3 rounded-2xl border border-white/15 bg-white/10 px-5 py-4 backdrop-blur-sm transition hover:bg-white/15">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-lg font-black text-blue-950">KC</span>
                        <span>
                            <span class="block text-2xl font-black text-orange-300">Kushwaha</span>
                            <span class="-mt-1 block text-2xl font-black text-green-300">Connect</span>
                        </span>
                    </Link>

                    <div class="mt-20 max-w-xl">
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Digital Community Platform</p>
                        <h1 class="mt-4 text-5xl font-black leading-tight tracking-tight">
                            Connecting, Supporting and Empowering the Kushwaha Community
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-blue-100">
                            Create your account, manage your community profile, connect safely, and access help, opportunities, events, and matrimonial services.
                        </p>
                    </div>
                </div>

                <div class="relative grid grid-cols-3 gap-4">
                    <div class="rounded-3xl bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-2xl font-black text-orange-300">01</p>
                        <p class="mt-2 text-sm font-bold text-blue-100">Community Profile</p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-2xl font-black text-green-300">02</p>
                        <p class="mt-2 text-sm font-bold text-blue-100">Trusted Connections</p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-5 backdrop-blur-sm">
                        <p class="text-2xl font-black text-orange-300">03</p>
                        <p class="mt-2 text-sm font-bold text-blue-100">Support Network</p>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6 lg:px-12">
                <div class="mx-auto w-full max-w-md">
                    <div class="mb-8 text-center lg:hidden">
                        <Link :href="route('welcome')" class="inline-flex items-center gap-3">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-lg font-black text-orange-300">KC</span>
                            <span class="text-left leading-tight">
                                <span class="block text-2xl font-black text-blue-950">Kushwaha</span>
                                <span class="-mt-1 block text-2xl font-black text-green-700">Connect</span>
                            </span>
                        </Link>
                    </div>

                    <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-2xl shadow-blue-950/10 sm:p-8">
                        <slot />
                    </div>

                    <AppVersionFooter class="mt-6 text-center" />
                </div>
            </section>
        </div>
    </div>
</template>
