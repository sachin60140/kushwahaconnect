<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    resources: Object
});

const form = useForm({});

const destroy = (id) => {
    if (confirm('Are you sure you want to delete this resource?')) {
        form.delete(route('admin.education.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Manage Education Resources" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Education Resources Management</h2>
                <Link :href="route('admin.education.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-sm shadow-sm">
                    Add Resource
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="resource in resources.data" :key="resource.id">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="font-bold">{{ resource.title }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ resource.description }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ resource.category }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                            :class="resource.is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                          {{ resource.is_published ? 'Published' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <Link :href="route('admin.education.edit', resource.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                        <button @click="destroy(resource.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="resources.data.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">No resources found.</td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- Pagination -->
                        <div v-if="resources.links.length > 3" class="mt-6 flex justify-center">
                            <div class="flex shadow-xs rounded-md">
                                <template v-for="(link, p) in resources.links" :key="p">
                                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm" v-html="link.label" />
                                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-white font-bold text-indigo-600': link.active }" :href="link.url" v-html="link.label" />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
