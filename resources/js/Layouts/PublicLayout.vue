<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AnnouncementBar from '@/Components/Home/AnnouncementBar.vue';
import { useLanguage } from '@/composables/useLanguage';

const { locale, __ } = useLanguage();
const page = usePage();
const isMobileMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user);
const settings = computed(() => page.props.websiteSettings || {});

const whatsappHref = computed(() => {
    if (settings.value.whatsapp_group_link) {
        return settings.value.whatsapp_group_link;
    }

    const mobile = (settings.value.contact_mobile || '').replace(/\D/g, '');
    return mobile ? `https://wa.me/${mobile}` : '/contact';
});

const socialLinks = computed(() => [
    { label: __('social_whatsapp'), href: whatsappHref.value },
    { label: __('social_facebook'), href: settings.value.facebook_url },
    { label: __('social_instagram'), href: settings.value.instagram_url },
    { label: __('social_youtube'), href: settings.value.youtube_url },
    { label: __('social_telegram'), href: settings.value.telegram_url },
    { label: __('social_linkedin'), href: settings.value.linkedin_url },
    { label: __('social_x'), href: settings.value.x_url },
].filter((link) => link.href));

const navLinks = computed(() => [
    { label: __('menu_home'), href: route('welcome') },
    { label: __('menu_about'), href: '/about' },
    { label: __('menu_directory'), href: route('directory') },
    { label: __('menu_businesses'), href: route('businesses.index') },
    { label: __('menu_matrimonial'), href: route('matrimonial') },
    { label: __('menu_help'), href: route('help.index') },
    { label: __('menu_news'), href: route('news.index') },
    { label: __('menu_gallery'), href: route('gallery.index') },
    { label: __('menu_volunteer'), href: route('volunteer.index') },
]);
</script>

<template>
    <div class="min-h-screen bg-[#fffaf0] pb-20 text-slate-900 antialiased md:pb-0" :class="locale === 'hi' ? 'font-devanagari' : 'font-sans'">
        <AnnouncementBar />

        <header class="sticky top-0 z-50 border-b border-blue-950/10 bg-white/95 shadow-sm backdrop-blur">
            <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex min-h-20 items-center justify-between gap-4">
                    <Link :href="route('welcome')" class="flex items-center gap-3" aria-label="KushwahaConnect home">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-lg font-black text-orange-400 shadow-sm">
                            KC
                        </span>
                        <span class="leading-tight">
                            <span class="block text-xl font-black tracking-tight text-blue-950">Kushwaha</span>
                            <span class="block -mt-1 text-xl font-black tracking-tight text-green-700">Connect</span>
                        </span>
                    </Link>

                    <div class="hidden items-center gap-5 lg:flex">
                        <Link
                            v-for="link in navLinks"
                            :key="link.href"
                            :href="link.href"
                            class="text-sm font-bold text-slate-700 transition hover:text-orange-600"
                        >
                            {{ link.label }}
                        </Link>
                    </div>

                    <div class="hidden items-center gap-2 lg:flex">
                        <div class="flex rounded-full bg-slate-100 p-1 text-xs font-bold">
                            <a
                                href="/lang/hi"
                                class="rounded-full px-3 py-2 transition"
                                :class="locale === 'hi' ? 'bg-white text-blue-950 shadow-sm' : 'text-slate-600 hover:text-blue-950'"
                            >
                                {{ __('language_hindi') }}
                            </a>
                            <a
                                href="/lang/en"
                                class="rounded-full px-3 py-2 transition"
                                :class="locale === 'en' ? 'bg-white text-blue-950 shadow-sm' : 'text-slate-600 hover:text-blue-950'"
                            >
                                {{ __('language_english') }}
                            </a>
                        </div>

                        <template v-if="user">
                            <Link :href="route('dashboard')" class="inline-flex min-h-11 items-center justify-center rounded-full bg-blue-950 px-5 text-sm font-bold text-white transition hover:bg-blue-900">
                                {{ __('menu_dashboard') }}
                            </Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="inline-flex min-h-11 items-center justify-center rounded-full border border-blue-950/15 px-5 text-sm font-bold text-blue-950 transition hover:border-blue-950 hover:bg-blue-50">
                                {{ __('menu_login') }}
                            </Link>
                            <Link :href="route('join')" class="inline-flex min-h-11 items-center justify-center rounded-full bg-orange-500 px-5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-600">
                                {{ __('menu_join') }}
                            </Link>
                        </template>
                    </div>

                    <button
                        type="button"
                        class="inline-flex min-h-12 min-w-12 items-center justify-center rounded-full border border-slate-200 text-blue-950 lg:hidden"
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        aria-label="Toggle navigation menu"
                    >
                        <svg v-if="!isMobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </nav>

            <div v-show="isMobileMenuOpen" class="border-t border-slate-200 bg-white lg:hidden">
                <div class="mx-auto max-w-7xl space-y-1 px-4 py-4">
                    <Link
                        v-for="link in navLinks"
                        :key="link.href"
                        :href="link.href"
                        class="block min-h-11 rounded-lg px-3 py-2.5 text-base font-bold text-slate-700 transition hover:bg-orange-50 hover:text-orange-600"
                        @click="isMobileMenuOpen = false"
                    >
                        {{ link.label }}
                    </Link>

                    <div class="grid grid-cols-2 gap-2 pt-3">
                        <a href="/lang/hi" class="rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-blue-950">
                            {{ __('language_hindi') }}
                        </a>
                        <a href="/lang/en" class="rounded-xl bg-slate-100 px-4 py-3 text-center text-sm font-bold text-blue-950">
                            {{ __('language_english') }}
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                        <Link
                            :href="user ? route('dashboard') : route('login')"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl border border-blue-950/15 px-4 text-sm font-bold text-blue-950"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ user ? __('menu_dashboard') : __('menu_login') }}
                        </Link>
                        <Link
                            :href="route('join')"
                            class="inline-flex min-h-12 items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-bold text-white"
                            @click="isMobileMenuOpen = false"
                        >
                            {{ __('menu_join') }}
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section v-if="$slots.header" class="border-b border-white/10 bg-blue-950 text-white">
                <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </section>
            <slot />
        </main>

        <footer class="border-t-4 border-orange-500 bg-blue-950 text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-lg font-black text-blue-950">KC</span>
                        <div class="leading-tight">
                            <span class="block text-xl font-black text-orange-400">Kushwaha</span>
                            <span class="block -mt-1 text-xl font-black text-green-400">Connect</span>
                        </div>
                    </div>
                    <p class="mt-5 max-w-sm text-sm leading-6 text-blue-100">
                        {{ __('footer_brand') }}
                    </p>
                    <div v-if="socialLinks.length" class="mt-5 flex flex-wrap gap-2">
                        <a
                            v-for="link in socialLinks"
                            :key="link.label"
                            :href="link.href"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-full bg-white/10 px-3 py-2 text-xs font-bold text-white transition hover:bg-orange-500"
                        >
                            {{ link.label }}
                        </a>
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-black text-orange-300">{{ __('footer_quick_links') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm text-blue-100">
                        <li><Link :href="route('join')" class="hover:text-white">{{ __('menu_join') }}</Link></li>
                        <li><Link :href="route('directory')" class="hover:text-white">{{ __('menu_directory') }}</Link></li>
                        <li><Link :href="route('businesses.index')" class="hover:text-white">{{ __('menu_businesses') }}</Link></li>
                        <li><Link :href="route('matrimonial')" class="hover:text-white">{{ __('menu_matrimonial') }}</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-base font-black text-orange-300">{{ __('footer_support_links') }}</h3>
                    <ul class="mt-4 space-y-3 text-sm text-blue-100">
                        <li><Link :href="route('help.index')" class="hover:text-white">{{ __('menu_help') }}</Link></li>
                        <li><Link :href="route('privacy')" class="hover:text-white">{{ __('footer_privacy') }}</Link></li>
                        <li><Link :href="route('terms')" class="hover:text-white">{{ __('footer_terms') }}</Link></li>
                        <li><Link :href="route('data-deletion')" class="hover:text-white">{{ __('footer_deletion') }}</Link></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-base font-black text-orange-300">{{ __('footer_contact') }}</h3>
                    <div class="mt-4 space-y-2 text-sm text-blue-100">
                        <p v-if="settings.contact_email">{{ settings.contact_email }}</p>
                        <p v-if="settings.contact_mobile">{{ settings.contact_mobile }}</p>
                        <Link href="/contact" class="inline-flex font-bold text-white hover:text-orange-300">{{ __('menu_contact') }}</Link>
                    </div>

                    <div class="mt-6">
                        <h4 class="text-sm font-black text-white">{{ __('footer_newsletter') }}</h4>
                        <p class="mt-2 text-sm text-blue-100">{{ __('footer_newsletter_desc') }}</p>
                        <form class="mt-3 flex gap-2" @submit.prevent>
                            <input
                                type="email"
                                :placeholder="__('footer_email_placeholder')"
                                class="min-w-0 flex-1 rounded-full border-0 bg-white/95 px-4 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-orange-400"
                            >
                            <button type="submit" class="rounded-full bg-orange-500 px-4 py-2 text-sm font-bold text-white transition hover:bg-orange-600">
                                {{ __('footer_subscribe') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="border-t border-white/10 px-4 py-5 text-center text-sm text-blue-200">
                &copy; {{ new Date().getFullYear() }} KushwahaConnect.com. {{ __('footer_rights') }}
            </div>
        </footer>

        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-3 py-2 shadow-2xl backdrop-blur md:hidden">
            <div class="grid grid-cols-3 gap-2">
                <Link :href="route('join')" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-orange-500 px-2 text-center text-xs font-black text-white">
                    {{ __('menu_join') }}
                </Link>
                <Link :href="route('matrimonial')" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-green-700 px-2 text-center text-xs font-black text-white">
                    {{ __('menu_matrimonial') }}
                </Link>
                <Link :href="user ? route('dashboard') : route('login')" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-blue-950 px-2 text-center text-xs font-black text-white">
                    {{ user ? __('menu_dashboard') : __('menu_login') }}
                </Link>
            </div>
        </div>
    </div>
</template>
