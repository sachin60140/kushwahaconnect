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

const { locale, __ } = useLanguage();

const localized = (key, fallback) => {
    const localizedKey = `${key}_${locale.value}`;
    return props.settings[localizedKey] || fallback;
};

const founderPhoto = computed(() => props.settings.founder_photo_url || '');
const founderName = computed(() => props.settings.founder_name || 'Kumar Sachin');
const whatsappHref = computed(() => {
    if (props.settings.founder_whatsapp_url) {
        return props.settings.founder_whatsapp_url;
    }

    const mobile = (props.settings.contact_mobile || '').replace(/\D/g, '');
    return mobile ? `https://wa.me/${mobile}` : null;
});

const links = computed(() => [
    { label: 'WhatsApp', href: whatsappHref.value },
    { label: 'Facebook', href: props.settings.founder_facebook_url || props.settings.facebook_url },
    { label: 'Instagram', href: props.settings.founder_instagram_url || props.settings.instagram_url },
    { label: __('founder_profile'), href: props.settings.founder_profile_url },
].filter((link) => link.href));
</script>

<template>
    <section class="bg-blue-950 px-4 py-16 text-white sm:px-6 lg:px-8">
        <div class="mx-auto grid max-w-5xl items-center gap-8 lg:grid-cols-[auto_1fr]">
            <div class="mx-auto">
                <div class="flex h-40 w-40 items-center justify-center overflow-hidden rounded-[2rem] border-4 border-orange-400 bg-orange-100 text-4xl font-black text-orange-700 shadow-2xl">
                    <img v-if="founderPhoto" :src="founderPhoto" :alt="founderName" class="h-full w-full object-cover" loading="lazy">
                    <span v-else>KS</span>
                </div>
            </div>

            <div class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur sm:p-8">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('founder_title') }}</p>
                <h2 class="mt-3 text-3xl font-black text-white">{{ founderName }}</h2>
                <p class="mt-5 text-lg font-bold text-blue-50">{{ __('founder_hello') }}</p>
                <p class="mt-3 text-base leading-8 text-blue-100">
                    {{ localized('founder_intro', __('founder_p1')) }}
                </p>
                <p class="mt-3 text-base leading-8 text-blue-100">
                    {{ localized('founder_message', __('founder_p2')) }}
                </p>
                <p class="mt-3 text-base font-bold leading-8 text-white">
                    {{ localized('founder_note', __('founder_p3')) }}
                </p>

                <div class="mt-7 flex flex-wrap gap-3">
                    <a
                        v-for="link in links"
                        :key="link.label"
                        :href="link.href"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-white px-5 text-sm font-black text-blue-950 transition hover:bg-orange-50"
                    >
                        {{ link.label }}
                    </a>
                    <Link href="/contact" class="inline-flex min-h-11 items-center justify-center rounded-full bg-orange-500 px-5 text-sm font-black text-white transition hover:bg-orange-600">
                        {{ __('menu_contact') }}
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
