<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

defineProps({
    members: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const form = useForm({ receiver_id: null });
const { __ } = useLanguage();
const user = computed(() => page.props.auth?.user);

const initials = (name) => (name || 'KC').split(' ').map((part) => part[0]).join('').slice(0, 2).toUpperCase();

const connect = (member) => {
    form.receiver_id = member.user_id;
    form.post(route('user.connections.store'), {
        preserveScroll: true,
        onFinish: () => {
            form.receiver_id = null;
        },
    });
};
</script>

<template>
    <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ __('directory_title') }}</h2>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ __('directory_desc') }}</p>
                </div>
                <Link :href="route('directory')" class="inline-flex min-h-12 items-center justify-center rounded-full border border-blue-950/15 px-6 text-sm font-black text-blue-950 transition hover:border-blue-950 hover:bg-blue-50">
                    {{ __('btn_view_directory') }}
                </Link>
            </div>

            <div v-if="members.length" class="mt-9 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <article
                    v-for="member in members"
                    :key="member.id"
                    class="overflow-hidden rounded-3xl border border-slate-100 bg-[#fffaf0] shadow-xs transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10"
                >
                    <div class="h-28 bg-linear-to-br from-blue-950 via-blue-900 to-green-700"></div>
                    <div class="-mt-12 p-5">
                        <div class="flex items-end justify-between gap-3">
                            <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-3xl border-4 border-[#fffaf0] bg-orange-100 text-xl font-black text-orange-700 shadow-md">
                                <img v-if="member.photo_url" :src="member.photo_url" :alt="member.first_name" class="h-full w-full object-cover" loading="lazy">
                                <span v-else>{{ initials(member.first_name) }}</span>
                            </div>
                            <span v-if="member.verified" class="rounded-full bg-green-100 px-3 py-1 text-xs font-black text-green-800">
                                {{ __('verified') }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-xl font-black text-blue-950">{{ member.first_name }}</h3>
                        <p class="mt-1 text-sm font-bold text-slate-600">{{ member.profession || __('profession_fallback') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ member.district || __('district_fallback') }}</p>

                        <div class="mt-5">
                            <button
                                v-if="user && member.user_id && user.id !== member.user_id"
                                type="button"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-blue-950 px-5 text-sm font-black text-white transition hover:bg-blue-900 disabled:opacity-60"
                                :disabled="form.processing && form.receiver_id === member.user_id"
                                @click="connect(member)"
                            >
                                {{ __('btn_connect') }}
                            </button>
                            <Link
                                v-else-if="!user"
                                :href="route('login')"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-full bg-blue-950 px-5 text-sm font-black text-white transition hover:bg-blue-900"
                            >
                                {{ __('btn_connect') }}
                            </Link>
                            <Link
                                v-else
                                :href="route('directory')"
                                class="inline-flex min-h-11 w-full items-center justify-center rounded-full border border-blue-950/15 px-5 text-sm font-black text-blue-950"
                            >
                                {{ __('btn_view_directory') }}
                            </Link>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="mt-9 rounded-3xl border border-dashed border-slate-300 bg-[#fffaf0] p-10 text-center">
                <p class="text-base font-bold text-slate-600">{{ __('directory_empty') }}</p>
            </div>
        </div>
    </section>
</template>
