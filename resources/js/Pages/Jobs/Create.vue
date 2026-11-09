<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    title: '',
    company: '',
    location: '',
    description: '',
    requirements: '',
    salary_range: '',
    contact_email: ''
});

const submit = () => {
    form.post(route('jobs.store'));
};
</script>

<template>
    <Head title="Post a Job" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center bg-white shadow-sm py-6 px-4 sm:px-6 lg:px-8 border-b border-gray-200">
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">Post a Job</h2>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-md sm:rounded-xl border-t-4 border-green-500">
                    <div class="p-8 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.title" required class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.company" required class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.company" class="text-red-500 text-xs mt-1">{{ form.errors.company }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.location" required placeholder="City, State or Remote" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.location" class="text-red-500 text-xs mt-1">{{ form.errors.location }}</div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Job Description <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="5" required class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Requirements (Optional)</label>
                                <textarea v-model="form.requirements" rows="4" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200"></textarea>
                                <div v-if="form.errors.requirements" class="text-red-500 text-xs mt-1">{{ form.errors.requirements }}</div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Salary Range (Optional)</label>
                                    <input type="text" v-model="form.salary_range" placeholder="e.g. ₹5,00,000 - ₹8,00,000" class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.salary_range" class="text-red-500 text-xs mt-1">{{ form.errors.salary_range }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Email <span class="text-red-500">*</span></label>
                                    <input type="email" v-model="form.contact_email" required class="block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.contact_email" class="text-red-500 text-xs mt-1">{{ form.errors.contact_email }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-4 border-t border-gray-200 pt-6 mt-6">
                                <Link :href="route('jobs.index')" class="bg-white border border-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded-lg shadow-xs hover:bg-gray-50 transition-colors duration-200">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white font-bold px-6 py-2.5 rounded-lg shadow-sm hover:bg-orange-600 disabled:opacity-50 transition-colors duration-200">Submit for Approval</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
