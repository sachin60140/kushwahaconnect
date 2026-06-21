<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    category: 'General',
    description: '',
    link_url: '',
    deadline_date: '',
    is_published: true
});

const submit = () => {
    form.post(route('admin.education.store'));
};
</script>

<template>
    <Head title="Create Education Resource" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Education Resource</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                <select v-model="form.category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="Scholarship">Scholarship</option>
                                    <option value="Course">Course</option>
                                    <option value="Mentorship">Mentorship</option>
                                    <option value="General">General</option>
                                </select>
                                <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Link URL</label>
                                    <input type="url" v-model="form.link_url" placeholder="https://" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.link_url" class="text-red-500 text-xs mt-1">{{ form.errors.link_url }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Deadline Date</label>
                                    <input type="date" v-model="form.deadline_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.deadline_date" class="text-red-500 text-xs mt-1">{{ form.errors.deadline_date }}</div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input id="is_published" type="checkbox" v-model="form.is_published" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_published" class="ml-2 block text-sm text-gray-900">
                                    Published
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 border-t pt-4">
                                <Link :href="route('admin.education.index')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 disabled:opacity-50">Save Resource</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
