<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();

const props = defineProps({
    businessListings: {
        type: Array,
        default: () => [],
    },
});

const user = usePage().props.auth.user;

const profileCompletion = user.status === 'profile_incomplete' ? 30 : 100;
const statusColor = user.status === 'approved' ? 'bg-green-100 text-green-800' : 
                    user.status === 'pending_approval' ? 'bg-yellow-100 text-yellow-800' : 
                    user.status === 'blocked' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800';

const formatStatus = (status) => {
    return status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const businessStatusClass = (status) => {
    return {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        approved: 'bg-green-100 text-green-800 border-green-200',
        rejected: 'bg-red-100 text-red-800 border-red-200',
    }[status] || 'bg-slate-100 text-slate-700 border-slate-200';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-IN', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="User Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard Overview
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">

                <!-- Mobile verification reminder -->
                <div v-if="user.mobile && !user.mobile_verified_at" class="mx-4 flex flex-col gap-3 rounded-xl border border-orange-200 bg-orange-50 p-4 sm:mx-0 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-base font-bold text-orange-900">{{ __('otp_banner_title') }}</p>
                        <p class="mt-1 text-sm text-orange-800">{{ __('otp_banner_text') }}</p>
                    </div>
                    <Link :href="route('mobile.verify.show')" class="flex min-h-11 shrink-0 items-center justify-center rounded-xl bg-orange-600 px-5 py-3 text-base font-bold text-white hover:bg-orange-500">
                        {{ __('otp_banner_cta') }}
                    </Link>
                </div>

                <!-- Welcome & Status Banner -->
                <div class="overflow-hidden bg-white shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900 flex flex-col md:flex-row justify-between items-center">
                        <div>
                            <h3 class="text-2xl font-bold mb-2">Welcome back, {{ user.name }}!</h3>
                            <p class="text-gray-600">Here's what's happening with your account today.</p>
                        </div>
                        <div class="mt-4 md:mt-0 flex items-center space-x-4">
                            <div class="text-right">
                                <p class="text-sm text-gray-500 mb-1">Account Status</p>
                                <span :class="statusColor" class="px-3 py-1 rounded-full text-sm font-bold shadow-xs">
                                    {{ formatStatus(user.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Left Column: Quick Actions & Profile Status -->
                    <div class="md:col-span-2 space-y-6">
                        
                        <!-- Profile Completion Alert -->
                        <div v-if="profileCompletion < 100" class="bg-orange-50 border-l-4 border-orange-500 p-4 shadow-xs rounded-r-lg">
                            <div class="flex items-center">
                                <div class="shrink-0">
                                    <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-orange-800">Complete your profile</h3>
                                    <div class="mt-2 text-sm text-orange-700">
                                        <p>Your profile is only {{ profileCompletion }}% complete. To unlock all community features, please add your details.</p>
                                    </div>
                                    <div class="mt-4">
                                        <div class="w-full bg-orange-200 rounded-full h-2.5 mb-2">
                                            <div class="bg-orange-500 h-2.5 rounded-full" :style="`width: ${profileCompletion}%`"></div>
                                        </div>
                                        <Link :href="route('user.member-profile.edit')" class="text-sm font-bold text-orange-700 hover:text-orange-600 underline">
                                            Complete Community Profile &rarr;
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Hub Widgets -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100 hover:shadow-md transition relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-50 rounded-full group-hover:bg-green-100 transition duration-300 z-0"></div>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-1">Community Members</h4>
                                    <p class="text-sm text-gray-500 mb-4">Connect with Kushwaha community members worldwide.</p>
                                    <Link :href="route('directory')" class="text-sm text-green-600 font-semibold hover:underline">View Directory</Link>
                                </div>
                            </div>

                            <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100 hover:shadow-md transition relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-pink-50 rounded-full group-hover:bg-pink-100 transition duration-300 z-0"></div>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 bg-pink-100 text-pink-600 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-1">Matrimonial</h4>
                                    <p class="text-sm text-gray-500 mb-4">Manage your matrimonial profile and search for matches.</p>
                                    <Link :href="route('matrimonial-directory.index')" class="text-sm text-pink-600 font-semibold hover:underline">Find Matches</Link>
                                </div>
                            </div>

                            <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100 hover:shadow-md transition relative overflow-hidden group">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-50 rounded-full group-hover:bg-blue-100 transition duration-300 z-0"></div>
                                <div class="relative z-10">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mb-3">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-1">Volunteer & Help</h4>
                                    <p class="text-sm text-gray-500 mb-4">Offer your skills or request help from the community.</p>
                                    <Link :href="route('volunteer.create')" class="text-sm text-blue-600 font-semibold hover:underline">Get Involved</Link>
                                </div>
                            </div>

                            <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100 hover:shadow-md transition relative overflow-hidden group sm:col-span-2">
                                <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-50 rounded-full group-hover:bg-purple-100 transition duration-300 z-0"></div>
                                <div class="relative z-10">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <div class="w-10 h-10 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mb-3">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m3-4h1m-1 4h1m-5 8h8"></path></svg>
                                            </div>
                                            <h4 class="text-lg font-bold text-gray-800 mb-1">My Business</h4>
                                            <p class="text-sm text-gray-500">Track submitted business listing approval status.</p>
                                        </div>
                                        <Link :href="route('businesses.create')" class="shrink-0 rounded-full bg-orange-500 px-4 py-2 text-xs font-black text-white shadow-xs transition hover:bg-orange-600">
                                            Add Business
                                        </Link>
                                    </div>

                                    <div v-if="props.businessListings.length" class="mt-5 space-y-3">
                                        <div
                                            v-for="business in props.businessListings"
                                            :key="business.id"
                                            class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4"
                                        >
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-base font-black text-blue-950">{{ business.business_name }}</p>
                                                    <p class="mt-1 text-xs font-bold text-slate-500">
                                                        {{ business.category }} - Submitted {{ formatDate(business.created_at) }}
                                                    </p>
                                                </div>
                                                <span class="inline-flex w-fit items-center rounded-full border px-3 py-1 text-xs font-black" :class="businessStatusClass(business.status)">
                                                    {{ formatStatus(business.status) }}
                                                </span>
                                            </div>
                                            <p v-if="business.status === 'pending'" class="mt-3 text-xs font-semibold text-yellow-700">
                                                Waiting for admin review. It will appear publicly after approval.
                                            </p>
                                            <p v-else-if="business.status === 'approved'" class="mt-3 text-xs font-semibold text-green-700">
                                                Approved and visible in the community business directory.
                                            </p>
                                            <p v-else-if="business.status === 'rejected'" class="mt-3 text-xs font-semibold text-red-700">
                                                Rejected by admin. Please contact support or submit updated details.
                                            </p>
                                        </div>
                                    </div>

                                    <div v-else class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center">
                                        <p class="text-sm font-bold text-gray-700">No business submitted yet.</p>
                                        <p class="mt-1 text-xs text-gray-500">Submit your business to promote it in the KushwahaConnect directory after admin approval.</p>
                                        <Link :href="route('businesses.create')" class="mt-4 inline-flex text-sm text-purple-600 font-semibold hover:underline">List Your Business</Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Sidebar info -->
                    <div class="space-y-6">
                        <!-- Recent Activity / Notifications -->
                        <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100">
                            <div class="flex items-center justify-between mb-4 border-b pb-2">
                                <h4 class="text-lg font-bold text-gray-800">Recent Activity</h4>
                                <span class="text-xs text-orange-600 font-bold bg-orange-100 px-2 py-1 rounded-sm">New</span>
                            </div>
                            <div class="rounded-lg bg-gray-50 border border-gray-100 px-4 py-6 text-center">
                                <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                                </div>
                                <p class="text-sm font-medium text-gray-800">No recent notifications yet.</p>
                                <p class="mt-1 text-xs text-gray-500">Community updates and connection requests will appear here.</p>
                            </div>
                            <Link :href="route('user.notifications.index')" class="block text-center w-full mt-4 py-2 border border-gray-200 text-sm font-medium rounded-md text-gray-600 hover:bg-gray-50 transition">
                                View All Notifications
                            </Link>
                        </div>

                        <!-- Connections Snippet -->
                        <div class="bg-white p-5 shadow-xs rounded-lg border border-gray-100">
                            <h4 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">My Connections</h4>
                            <div class="text-center py-6">
                                <div class="mx-auto w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 mb-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                                <p class="text-sm text-gray-500">You don't have any connections yet.</p>
                                <Link :href="route('directory')" class="mt-3 inline-block text-sm text-green-600 font-bold hover:underline">Find People</Link>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
