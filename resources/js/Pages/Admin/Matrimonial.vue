<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profiles: Object
});

const form = useForm({});

const updateStatus = (id, status) => {
    form.patch(route('admin.matrimonial.status', id), {
        data: { status: status },
        preserveScroll: true
    });
};

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this profile?')) {
        form.delete(route('admin.matrimonial.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Matrimonial Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Matrimonial Profiles
                </h2>
                <a :href="route('admin.matrimonial.export')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                    Export Excel
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3">Profile For</th>
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Gender</th>
                                    <th scope="col" class="px-6 py-3">City</th>
                                    <th scope="col" class="px-6 py-3">Status</th>
                                    <th scope="col" class="px-6 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="profile in profiles.data" :key="profile.id" class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ profile.profile_for }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ profile.full_name }}</td>
                                    <td class="px-6 py-4">{{ profile.gender }}</td>
                                    <td class="px-6 py-4">{{ profile.current_city }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded text-xs font-semibold"
                                            :class="{
                                                'bg-yellow-100 text-yellow-800': profile.status === 'pending',
                                                'bg-green-100 text-green-800': profile.status === 'approved',
                                                'bg-red-100 text-red-800': profile.status === 'rejected'
                                            }">
                                            {{ profile.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 space-x-2">
                                        <button v-if="profile.status !== 'approved'" @click="updateStatus(profile.id, 'approved')" class="text-green-600 hover:underline">Approve</button>
                                        <button v-if="profile.status !== 'rejected'" @click="updateStatus(profile.id, 'rejected')" class="text-red-600 hover:underline">Reject</button>
                                        <button @click="destroy(profile.id)" class="text-gray-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="profiles.data.length === 0" class="p-6 text-center text-gray-500">
                            No profiles found.
                        </div>
                    </div>
                </div>
                
                <!-- Pagination (simple) -->
                <div class="mt-4 flex justify-between" v-if="profiles.links">
                    <Link v-if="profiles.prev_page_url" :href="profiles.prev_page_url" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50">Previous</Link>
                    <div v-else></div>
                    <Link v-if="profiles.next_page_url" :href="profiles.next_page_url" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50">Next</Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
