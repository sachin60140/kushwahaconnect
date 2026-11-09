<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';

dayjs.extend(relativeTime);

const props = defineProps({
    notifications: Object,
});

const readForm = useForm({});
const deleteForm = useForm({});

const markAsRead = (id) => {
    readForm.patch(route('user.notifications.read', id), {
        preserveScroll: true,
    });
};

const markAllAsRead = () => {
    readForm.post(route('user.notifications.read-all'), {
        preserveScroll: true,
    });
};

const deleteNotification = (id) => {
    deleteForm.delete(route('user.notifications.destroy', id), {
        preserveScroll: true,
    });
};

</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Notifications
                </h2>
                <button v-if="notifications.data.length > 0" @click="markAllAsRead" class="text-sm text-orange-600 hover:text-orange-800 hover:underline font-medium">
                    Mark all as read
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-0 text-gray-900">
                        
                        <div v-if="notifications.data.length === 0" class="p-8 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <p class="mt-4 text-lg font-medium text-gray-900">No notifications yet</p>
                            <p class="mt-1">When you get connection requests or profile updates, they will show up here.</p>
                        </div>

                        <div v-else class="divide-y divide-gray-200">
                            <div v-for="notification in notifications.data" :key="notification.id" 
                                 class="p-6 flex items-start transition duration-150 ease-in-out hover:bg-gray-50"
                                 :class="{ 'bg-orange-50/50': !notification.read_at }">
                                
                                <!-- Icon based on type (assuming we will add types later, generic for now) -->
                                <div class="shrink-0 mt-1">
                                    <div class="h-10 w-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="ml-4 flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900" :class="{ 'font-bold': !notification.read_at }">
                                            {{ notification.data.title || 'New Notification' }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ dayjs(notification.created_at).fromNow() }}
                                        </p>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ notification.data.message || 'You have a new update.' }}
                                    </p>
                                    
                                    <div class="mt-3 flex gap-3">
                                        <button v-if="!notification.read_at" @click="markAsRead(notification.id)" class="text-xs text-orange-600 hover:text-orange-800 font-medium">
                                            Mark as read
                                        </button>
                                        <button @click="deleteNotification(notification.id)" class="text-xs text-gray-500 hover:text-red-600 font-medium">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="notifications.links && notifications.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                            <div class="flex flex-wrap -mb-1">
                                <template v-for="(link, p) in notifications.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                                    <Link v-else :href="link.url" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-orange-500 focus:text-orange-500" :class="{ 'bg-white font-bold': link.active }" v-html="link.label" />
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
