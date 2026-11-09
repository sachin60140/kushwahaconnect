<script setup>
import { computed } from 'vue';
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
    return mobile ? `https://wa.me/${mobile}` : '';
});

const icons = {
    whatsapp: {
        viewBox: '0 0 24 24',
        paths: [
            'M12.04 2a9.94 9.94 0 0 0-8.58 14.98L2.15 22l5.18-1.28A9.98 9.98 0 1 0 12.04 2Zm0 1.8a8.17 8.17 0 1 1-4.19 15.18l-.3-.18-3.08.76.79-3-.2-.32A8.14 8.14 0 0 1 12.04 3.8Zm-3.4 3.98c-.2 0-.5.08-.76.36-.26.28-1 1-1 2.42s1.02 2.8 1.17 3c.14.2 2 3.2 4.95 4.36 2.45.97 2.95.78 3.48.73.53-.05 1.72-.7 1.96-1.38.24-.68.24-1.26.17-1.38-.07-.12-.26-.2-.55-.35-.29-.14-1.72-.85-1.99-.95-.26-.1-.46-.14-.65.14-.19.29-.74.95-.91 1.15-.17.2-.34.22-.63.07-.29-.14-1.22-.45-2.33-1.44-.86-.77-1.44-1.72-1.6-2-.17-.29-.02-.44.13-.58.13-.13.29-.34.43-.51.14-.17.19-.29.29-.48.1-.2.05-.36-.02-.51-.07-.14-.65-1.58-.9-2.16-.23-.56-.48-.48-.65-.49h-.55Z',
        ],
    },
    facebook: {
        viewBox: '0 0 24 24',
        paths: [
            'M22 12a10 10 0 1 0-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.77-3.89 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0 0 22 12Z',
        ],
    },
    instagram: {
        viewBox: '0 0 24 24',
        paths: [
            'M7.8 2h8.4A5.8 5.8 0 0 1 22 7.8v8.4a5.8 5.8 0 0 1-5.8 5.8H7.8A5.8 5.8 0 0 1 2 16.2V7.8A5.8 5.8 0 0 1 7.8 2Zm0 2A3.8 3.8 0 0 0 4 7.8v8.4A3.8 3.8 0 0 0 7.8 20h8.4a3.8 3.8 0 0 0 3.8-3.8V7.8A3.8 3.8 0 0 0 16.2 4H7.8Zm9.65 1.55a1 1 0 1 1 0 2 1 1 0 0 1 0-2ZM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10Zm0 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
        ],
    },
    youtube: {
        viewBox: '0 0 24 24',
        paths: [
            'M21.58 7.18a2.75 2.75 0 0 0-1.94-1.95C17.93 4.77 12 4.77 12 4.77s-5.93 0-7.64.46a2.75 2.75 0 0 0-1.94 1.95A28.68 28.68 0 0 0 2 12a28.68 28.68 0 0 0 .42 4.82 2.75 2.75 0 0 0 1.94 1.95c1.71.46 7.64.46 7.64.46s5.93 0 7.64-.46a2.75 2.75 0 0 0 1.94-1.95A28.68 28.68 0 0 0 22 12a28.68 28.68 0 0 0-.42-4.82ZM10 15.27V8.73L15.45 12 10 15.27Z',
        ],
    },
    telegram: {
        viewBox: '0 0 24 24',
        paths: [
            'M21.75 4.3 18.5 19.64c-.25 1.08-.9 1.34-1.82.83l-4.96-3.66-2.39 2.3c-.26.27-.49.5-1 .5l.36-5.06 9.2-8.3c.4-.36-.09-.56-.62-.2L5.9 13.2.99 11.67c-1.07-.34-1.09-1.07.22-1.58L20.36 2.7c.89-.33 1.67.2 1.39 1.6Z',
        ],
    },
    linkedin: {
        viewBox: '0 0 24 24',
        paths: [
            'M4.98 3.5a2.5 2.5 0 1 1-.01 5.01 2.5 2.5 0 0 1 .01-5.01ZM3 9.75h4v11.5H3V9.75Zm6.25 0h3.83v1.57h.05c.53-1 1.84-2.06 3.8-2.06 4.06 0 4.82 2.67 4.82 6.15v5.84h-4v-5.18c0-1.24-.02-2.83-1.72-2.83-1.73 0-1.99 1.35-1.99 2.74v5.27h-3.99V9.75Z',
        ],
    },
    x: {
        viewBox: '0 0 24 24',
        paths: [
            'M18.9 2.5h3.26l-7.12 8.14 8.37 10.86h-6.56l-5.14-6.6-5.88 6.6H2.57l7.62-8.56L2.16 2.5h6.72l4.64 6.04L18.9 2.5Zm-1.14 17.08h1.8L7.9 4.32H5.96l11.8 15.26Z',
        ],
    },
};

const links = computed(() => [
    { label: __('social_whatsapp'), href: whatsappHref.value, color: 'from-green-500 to-green-700', icon: icons.whatsapp },
    { label: __('social_facebook'), href: props.settings.facebook_url, color: 'from-blue-500 to-blue-800', icon: icons.facebook },
    { label: __('social_instagram'), href: props.settings.instagram_url, color: 'from-orange-500 via-pink-500 to-purple-700', icon: icons.instagram },
    { label: __('social_youtube'), href: props.settings.youtube_url, color: 'from-red-500 to-red-700', icon: icons.youtube },
    { label: __('social_telegram'), href: props.settings.telegram_url, color: 'from-sky-400 to-sky-700', icon: icons.telegram },
    { label: __('social_linkedin'), href: props.settings.linkedin_url, color: 'from-blue-600 to-blue-900', icon: icons.linkedin },
    { label: __('social_x'), href: props.settings.x_url, color: 'from-slate-800 to-slate-950', icon: icons.x },
].filter((link) => link.href));
</script>

<template>
    <section id="social-connect" class="bg-[#fffaf0] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ __('social_title') }}</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">{{ __('social_desc') }}</p>
            </div>

            <div v-if="links.length" class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    v-for="link in links"
                    :key="link.label"
                    :href="link.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="group rounded-3xl bg-white p-5 shadow-xs transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10"
                >
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-linear-to-br text-white shadow-xs transition group-hover:scale-105" :class="link.color">
                        <svg
                            class="h-7 w-7"
                            :viewBox="link.icon.viewBox"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path
                                v-for="path in link.icon.paths"
                                :key="path"
                                :d="path"
                            />
                        </svg>
                    </span>
                    <span class="mt-5 block text-lg font-black text-blue-950 group-hover:text-orange-600">{{ link.label }}</span>
                    <span class="mt-2 block text-sm font-bold text-slate-500">{{ __('btn_social') }}</span>
                </a>
            </div>

            <div v-else class="mt-10 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="text-base font-bold text-slate-600">{{ __('social_desc') }}</p>
            </div>
        </div>
    </section>
</template>
