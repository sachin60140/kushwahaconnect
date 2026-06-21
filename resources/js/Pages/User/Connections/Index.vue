<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    receivedRequests: Array,
    sentRequests: Array,
    activeConnections: Array,
});

const updateForm = useForm({
    status: '',
});

const handleRequest = (id, status) => {
    updateForm.status = status;
    updateForm.patch(route('user.connections.update', id), {
        preserveScroll: true,
    });
};

const deleteForm = useForm({});

const removeConnection = (id) => {
    if (confirm('Are you sure you want to remove this connection?')) {
        deleteForm.delete(route('user.connections.destroy', id), {
            preserveScroll: true,
        });
    }
};

</script>

<template>
    <Head title="My Connections" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                My Connections
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                
                <!-- Received Requests -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Received Connection Requests</h3>
                        
                        <div v-if="receivedRequests.length === 0" class="text-gray-500 italic">
                            No pending connection requests.
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="req in receivedRequests" :key="req.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <div class="h-12 w-12 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-xl">
                                        {{ req.sender.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ req.sender.name }}</div>
                                        <div class="text-sm text-gray-500">{{ req.sender.member_profile?.city_village || 'Unknown Location' }}</div>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button @click="handleRequest(req.id, 'accepted')" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700">
                                        Accept
                                    </button>
                                    <button @click="handleRequest(req.id, 'rejected')" class="px-4 py-2 bg-red-100 text-red-700 text-sm font-medium rounded hover:bg-red-200">
                                        Decline
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Connections -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">My Connections</h3>
                        
                        <div v-if="activeConnections.length === 0" class="text-gray-500 italic">
                            You don't have any connections yet. Go to the <Link :href="route('directory')" class="text-orange-600 hover:underline">Community Directory</Link> to find people you know.
                        </div>

                        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="conn in activeConnections" :key="conn.id" class="p-4 bg-white rounded-lg border border-gray-200 shadow-sm flex flex-col justify-between">
                                <div class="flex items-center space-x-4 mb-4">
                                    <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xl">
                                        <!-- Determine if we are sender or receiver to show the other person -->
                                        {{ conn.sender_id === $page.props.auth.user.id ? conn.receiver.name.charAt(0) : conn.sender.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">
                                            {{ conn.sender_id === $page.props.auth.user.id ? conn.receiver.name : conn.sender.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ conn.sender_id === $page.props.auth.user.id ? (conn.receiver.member_profile?.city_village || 'Member') : (conn.sender.member_profile?.city_village || 'Member') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-end border-t pt-3">
                                    <button @click="removeConnection(conn.id)" class="text-sm text-red-600 hover:text-red-800 font-medium">
                                        Remove Connection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sent Requests -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Sent Requests (Pending)</h3>
                        
                        <div v-if="sentRequests.length === 0" class="text-gray-500 italic">
                            No pending sent requests.
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="req in sentRequests" :key="req.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                        {{ req.receiver.name.charAt(0) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ req.receiver.name }}</div>
                                    </div>
                                </div>
                                <div>
                                    <button @click="removeConnection(req.id)" class="text-sm text-gray-500 hover:text-gray-700 underline">
                                        Cancel Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
