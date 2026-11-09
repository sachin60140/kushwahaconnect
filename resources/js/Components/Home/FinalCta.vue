<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const { __ } = useLanguage();

const whatsappHref = computed(() => {
    if (props.settings.whatsapp_group_link) {
        return props.settings.whatsapp_group_link;
    }

    const mobile = (props.settings.contact_mobile || '').replace(/\D/g, '');
    return mobile ? `https://wa.me/${mobile}` : '/contact';
});
</script>

<template>
    <section class="bg-white px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl rounded-3xl bg-linear-to-br from-blue-950 via-blue-900 to-green-800 p-8 text-center text-white shadow-2xl shadow-blue-950/15 sm:p-10">
            <h2 class="text-3xl font-black tracking-tight sm:text-4xl">{{ __('final_cta_title') }}</h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-blue-100">{{ __('final_cta_desc') }}</p>
            <div class="mt-8 grid gap-3 sm:flex sm:justify-center">
                <Link :href="route('join')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-orange-500 px-7 text-base font-black text-white transition hover:bg-orange-600">
                    {{ __('btn_join') }}
                </Link>
                <Link :href="route('join')" class="inline-flex min-h-14 items-center justify-center rounded-full bg-white px-7 text-base font-black text-blue-950 transition hover:bg-orange-50">
                    {{ __('btn_submit_details') }}
                </Link>
                <a :href="whatsappHref" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-14 items-center justify-center rounded-full border border-white/20 bg-white/10 px-7 text-base font-black text-white transition hover:bg-white/15">
                    {{ __('btn_whatsapp') }}
                </a>
            </div>
        </div>
    </section>
</template>
