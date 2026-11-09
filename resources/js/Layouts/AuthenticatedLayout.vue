<script setup>
import { computed, ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import AppVersionFooter from '@/Components/AppVersionFooter.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    // Pages that render their own flash message pass :show-flash="false".
    showFlash: {
        type: Boolean,
        default: true,
    },
});

const showingNavigationDropdown = ref(false);
const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => ['admin', 'super_admin'].includes(user.value?.role));
const userName = computed(() => user.value?.name || 'Member');
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
const userContact = computed(() => user.value?.mobile || user.value?.email || 'KushwahaConnect member');

const userLinks = [
    { name: 'Dashboard', route: 'dashboard', active: 'dashboard', icon: 'M3 12 12 3l9 9M5 10v10h5v-6h4v6h5V10' },
    { name: 'My Profile', route: 'profile.edit', active: 'profile.*', icon: 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z' },
    { name: 'Community Profile', route: 'user.member-profile.edit', active: 'user.member-profile.*', icon: 'M17 20h5v-2a4 4 0 0 0-7.6-1.7M9 20H4v-2a4 4 0 0 1 7.6-1.7M12 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm8 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z' },
    { name: 'My Connections', route: 'user.connections.index', active: 'user.connections.*', icon: 'M13.8 10.2a4 4 0 0 0-5.6 0l-4 4a4 4 0 1 0 5.6 5.6l1.1-1.1m-.7-4.9a4 4 0 0 0 5.6 0l4-4a4 4 0 1 0-5.6-5.6l-1.1 1.1' },
    { name: 'Matrimonial Profile', route: 'user.matrimonial-profile.edit', active: 'user.matrimonial-profile.*', icon: 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z' },
    { name: 'Help Requests', route: 'help.index', active: 'help.*', icon: 'M12 8v8m-4-4h8m5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
    { name: 'Volunteering', route: 'volunteer.create', active: 'volunteer.*', icon: 'M12 4.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM4 21a8 8 0 0 1 16 0' },
    { name: 'Notifications', route: 'user.notifications.index', active: 'user.notifications.*', icon: 'M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1' },
    { name: 'Privacy Settings', route: 'user.privacy-settings.edit', active: 'user.privacy-settings.*', icon: 'M12 15v2m-6 4h12a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2Zm10-10V7a4 4 0 0 0-8 0v4h8Z' },
    { name: 'My Business', route: 'businesses.create', active: 'businesses.create', icon: 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1m-1 4h1m-1 4h1m6-4h1m-1 4h1' },
];

const adminLinks = [
    { name: 'Dashboard', route: 'dashboard', active: 'dashboard', icon: 'M3 12 12 3l9 9M5 10v10h5v-6h4v6h5V10' },
    { name: 'News & Events', route: 'admin.news.index', active: 'admin.news.*', icon: 'M19 20H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1m2 13a2 2 0 0 1-2-2V7m2 13a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M7 8h6v4H7V8Z' },
    { name: 'Kushwaha Pride', route: 'admin.pride.index', active: 'admin.pride.*', icon: 'M12 3l2.6 5.3 5.9.9-4.2 4.1 1 5.8L12 16.4 6.7 19.1l1-5.8L3.5 9.2l5.9-.9L12 3Z' },
    { name: 'Representatives', route: 'admin.representatives.index', active: 'admin.representatives.*', icon: 'M12 3 4 7v6c0 4.4 3.4 8 8 8s8-3.6 8-8V7l-8-4Zm0 4v10m-4-5h8' },
    { name: 'Nominations', route: 'admin.nominations.index', active: 'admin.nominations.*', icon: 'M12 6v12m6-6H6M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z' },
    { name: 'Corrections', route: 'admin.corrections.index', active: 'admin.corrections.*', icon: 'M16.9 3.6a2 2 0 0 1 2.8 2.8L8.6 17.5 4 19l1.5-4.6L16.9 3.6ZM4 21h16' },
    { name: 'Members Directory', route: 'admin.members', active: 'admin.members*', icon: 'M17 20h5v-2a4 4 0 0 0-7.6-1.7M9 20H4v-2a4 4 0 0 1 7.6-1.7M12 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm8 0a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z' },
    { name: 'Matrimonial', route: 'admin.matrimonial', active: 'admin.matrimonial*', icon: 'M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z' },
    { name: 'Jobs', route: 'admin.jobs.index', active: 'admin.jobs.*', icon: 'M21 13.2A24 24 0 0 1 12 15a24 24 0 0 1-9-1.8M16 6V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2M5 20h14a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z' },
    { name: 'Education', route: 'admin.education.index', active: 'admin.education.*', icon: 'M12 14 3 9l9-5 9 5-9 5Zm0 0v7M6 11.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5' },
    { name: 'Medical', route: 'admin.medical.index', active: 'admin.medical.*', icon: 'M12 6v12m-6-6h12M12 3a9 9 0 1 1 0 18 9 9 0 0 1 0-18Z' },
    { name: 'Help Requests', route: 'admin.help-requests.index', active: 'admin.help-requests.*', icon: 'M12 8v8m-4-4h8m5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z' },
    { name: 'Volunteers', route: 'admin.volunteers.index', active: 'admin.volunteers.*', icon: 'M12 4.5a4 4 0 1 1 0 8 4 4 0 0 1 0-8ZM4 21a8 8 0 0 1 16 0' },
    { name: 'Business Listings', route: 'admin.business.index', active: 'admin.business.*', icon: 'M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1m-1 4h1m-1 4h1m6-4h1m-1 4h1' },
    { name: 'Gallery', route: 'admin.gallery.index', active: 'admin.gallery.*', icon: 'M4 16l4.6-4.6a2 2 0 0 1 2.8 0L16 16m-2-2 1.6-1.6a2 2 0 0 1 2.8 0L20 14M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z' },
    { name: 'Enquiries', route: 'admin.enquiries', active: 'admin.enquiries*', icon: 'M3 8l7.9 5.3a2 2 0 0 0 2.2 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z' },
    { name: 'Settings', route: 'admin.settings.index', active: 'admin.settings.*', icon: 'M10.3 4.3c.4-1.8 2.9-1.8 3.4 0a1.7 1.7 0 0 0 2.6 1.1c1.5-.9 3.3.8 2.4 2.4a1.7 1.7 0 0 0 1 2.5c1.8.5 1.8 3 0 3.4a1.7 1.7 0 0 0-1 2.6c.9 1.5-.9 3.3-2.4 2.4a1.7 1.7 0 0 0-2.6 1c-.5 1.8-3 1.8-3.4 0a1.7 1.7 0 0 0-2.6-1c-1.5.9-3.3-.9-2.4-2.4a1.7 1.7 0 0 0-1-2.6c-1.8-.4-1.8-2.9 0-3.4a1.7 1.7 0 0 0 1-2.5c-.9-1.6.9-3.3 2.4-2.4a1.7 1.7 0 0 0 2.6-1ZM15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' },
];

const links = computed(() => isAdmin.value ? adminLinks : userLinks);

const isActive = (link) => route().current(link.active);
</script>

<template>
    <div class="min-h-screen bg-[#fffaf0] text-slate-900">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-white/10 bg-blue-950 text-white md:flex">
            <div class="flex h-20 items-center border-b border-white/10 px-6">
                <Link :href="route('welcome')" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-base font-black text-blue-950">KC</span>
                    <span class="leading-tight">
                        <span class="block text-xl font-black text-orange-300">Kushwaha</span>
                        <span class="-mt-1 block text-xl font-black text-green-300">Connect</span>
                    </span>
                </Link>
            </div>

            <div class="flex-1 overflow-y-auto px-4 py-5">
                <p class="px-3 text-xs font-black uppercase tracking-[0.2em] text-blue-200">
                    {{ isAdmin ? 'Admin Menu' : 'Member Menu' }}
                </p>
                <nav class="mt-4 space-y-1">
                    <Link
                        v-for="link in links"
                        :key="link.name"
                        :href="route(link.route)"
                        class="group flex items-center rounded-2xl px-3 py-3 text-sm font-bold transition"
                        :class="isActive(link) ? 'bg-white text-blue-950 shadow-sm' : 'text-blue-100 hover:bg-white/10 hover:text-white'"
                    >
                        <svg
                            class="mr-3 h-5 w-5 shrink-0 transition"
                            :class="isActive(link) ? 'text-orange-500' : 'text-blue-200 group-hover:text-orange-300'"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="link.icon" />
                        </svg>
                        {{ link.name }}
                    </Link>
                </nav>
            </div>

            <div class="border-t border-white/10 p-4">
                <Link :href="route('profile.edit')" class="flex items-center rounded-2xl bg-white/10 p-3 transition hover:bg-white/15">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-400 text-sm font-black uppercase text-blue-950">
                        {{ userInitial }}
                    </span>
                    <span class="ml-3 min-w-0">
                        <span class="block truncate text-sm font-black text-white">{{ userName }}</span>
                        <span class="block text-xs font-bold text-blue-200">View profile</span>
                    </span>
                </Link>
            </div>
        </aside>

        <div class="min-h-screen md:pl-72">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3 md:hidden">
                        <button
                            type="button"
                            class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-blue-950"
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                        >
                            <svg v-if="!showingNavigationDropdown" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                            <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <Link :href="route('welcome')" class="flex items-center gap-2">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-950 text-sm font-black text-orange-300">KC</span>
                            <span class="text-base font-black text-blue-950">KushwahaConnect</span>
                        </Link>
                    </div>

                    <div class="hidden md:block">
                        <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">
                            {{ isAdmin ? 'Administration' : 'Member Area' }}
                        </p>
                        <p class="mt-1 text-sm font-bold text-slate-500">Welcome, {{ userName }}</p>
                    </div>

                    <div class="ml-auto flex items-center gap-3">
                        <Link :href="route('welcome')" class="hidden min-h-11 items-center justify-center rounded-full border border-blue-950/15 px-5 text-sm font-black text-blue-950 transition hover:border-blue-950 hover:bg-blue-50 sm:inline-flex">
                            Public Site
                        </Link>
                        <Link :href="route('user.notifications.index')" class="relative inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 text-blue-950 transition hover:bg-orange-50">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1" />
                            </svg>
                            <span v-if="page.props.auth.unread_notifications_count > 0" class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-orange-500 text-[10px] font-black text-white ring-2 ring-white">
                                {{ page.props.auth.unread_notifications_count }}
                            </span>
                        </Link>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center rounded-full bg-blue-950 px-2 py-2 text-sm font-bold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-400 text-xs font-black uppercase text-blue-950">
                                        {{ userInitial }}
                                    </span>
                                    <span class="mx-2 hidden max-w-32 truncate sm:block">{{ userName }}</span>
                                    <svg class="mr-1 hidden h-4 w-4 sm:block" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 0 1 1.414 0L10 10.586l3.293-3.293a1 1 0 1 1 1.414 1.414l-4 4a1 1 0 0 1-1.414 0l-4-4a1 1 0 0 1 0-1.414Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile Settings</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>

                <div v-show="showingNavigationDropdown" class="border-t border-slate-200 bg-white md:hidden">
                    <div class="space-y-1 py-3">
                        <ResponsiveNavLink
                            v-for="link in links"
                            :key="link.name"
                            :href="route(link.route)"
                            :active="isActive(link)"
                        >
                            {{ link.name }}
                        </ResponsiveNavLink>
                    </div>
                    <div class="border-t border-slate-100 px-4 py-4">
                        <p class="text-base font-black text-blue-950">{{ userName }}</p>
                        <p class="text-sm font-bold text-slate-500">{{ userContact }}</p>
                    </div>
                </div>
            </header>

            <section v-if="$slots.header" class="border-b border-slate-200 bg-white">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </section>

            <main class="min-h-[calc(100vh-5rem)]">
                <FlashMessages v-if="showFlash" />
                <slot />
            </main>

            <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center sm:px-6 lg:px-8">
                <p class="text-xs text-slate-500">&copy; {{ new Date().getFullYear() }} KushwahaConnect.com</p>
                <AppVersionFooter class="mt-1" />
            </footer>
        </div>
    </div>
</template>
