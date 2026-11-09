<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    resource: Object
});

const form = useForm({
    title: props.resource.title,
    type: props.resource.type,
    description: props.resource.description,
    contact_info: props.resource.contact_info,
    blood_group: props.resource.blood_group || '',
    urgency: props.resource.urgency || '',
    is_published: props.resource.is_published
});

const submit = () => {
    form.put(route('admin.medical.update', props.resource.id));
};
</script>

<template>
    <Head title="Edit Medical Resource" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Medical Resource</h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                                <select v-model="form.type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="blood_request">Blood Request</option>
                                    <option value="doctor">Doctor</option>
                                    <option value="hospital">Hospital</option>
                                    <option value="other">Other</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>
                            </div>

                            <div v-if="form.type === 'blood_request'" class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-red-50 p-4 rounded-md border border-red-100">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Blood Group <span class="text-red-500">*</span></label>
                                    <select v-model="form.blood_group" :required="form.type === 'blood_request'" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Select Blood Group</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                    </select>
                                    <div v-if="form.errors.blood_group" class="text-red-500 text-xs mt-1">{{ form.errors.blood_group }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Urgency Level</label>
                                    <select v-model="form.urgency" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Normal</option>
                                        <option value="High">High</option>
                                        <option value="Critical">Critical</option>
                                    </select>
                                    <div v-if="form.errors.urgency" class="text-red-500 text-xs mt-1">{{ form.errors.urgency }}</div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="5" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Provide details like location, patient name (if applicable), requirements..."></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Information <span class="text-red-500">*</span></label>
                                <textarea v-model="form.contact_info" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Phone numbers, hospital address, contact person..."></textarea>
                                <div v-if="form.errors.contact_info" class="text-red-500 text-xs mt-1">{{ form.errors.contact_info }}</div>
                            </div>

                            <div class="flex items-center">
                                <input id="is_published" type="checkbox" v-model="form.is_published" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded-sm">
                                <label for="is_published" class="ml-2 block text-sm text-gray-900">
                                    Published
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 border-t pt-4">
                                <Link :href="route('admin.medical.index')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-sm shadow-xs hover:bg-gray-50">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-red-600 text-white px-4 py-2 rounded-sm shadow-sm hover:bg-red-700 disabled:opacity-50">Update Resource</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
