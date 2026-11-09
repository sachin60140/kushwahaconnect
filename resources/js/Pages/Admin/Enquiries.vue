<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    enquiries: Object
});
</script>

<template>
    <Head title="Contact Enquiries" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Contact Enquiries
                </h2>
                <a :href="route('admin.enquiries.export')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
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
                                    <th scope="col" class="px-6 py-3">Name</th>
                                    <th scope="col" class="px-6 py-3">Mobile</th>
                                    <th scope="col" class="px-6 py-3">Purpose</th>
                                    <th scope="col" class="px-6 py-3">Message</th>
                                    <th scope="col" class="px-6 py-3">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="enq in enquiries.data" :key="enq.id" class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ enq.name }}</td>
                                    <td class="px-6 py-4">{{ enq.mobile }}</td>
                                    <td class="px-6 py-4">{{ enq.purpose }}</td>
                                    <td class="px-6 py-4 truncate max-w-xs" :title="enq.message">{{ enq.message }}</td>
                                    <td class="px-6 py-4">{{ new Date(enq.created_at).toLocaleDateString() }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="enquiries.data.length === 0" class="p-6 text-center text-gray-500">
                            No enquiries found.
                        </div>
                    </div>
                </div>

                <div v-if="enquiries.links && enquiries.links.length > 3" class="mt-6 flex flex-wrap">
                    <template v-for="(link, p) in enquiries.links" :key="p">
                        <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded bg-white" v-html="link.label" />
                        <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white': !link.active }" :href="link.url" v-html="link.label" />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
