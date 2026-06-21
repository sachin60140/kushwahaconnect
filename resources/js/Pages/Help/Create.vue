<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const user = usePage().props.auth.user;

const form = useForm({
    name: user ? user.name : '',
    mobile: user ? user.mobile : '',
    whatsapp: '',
    state: '',
    district: '',
    city: '',
    category: 'Education Help',
    title: '',
    description: '',
    urgency: 'Normal',
    is_public: true
});

const submit = () => {
    form.post(route('help.store'));
};
</script>

<template>
    <Head title="Request Help" />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Community Support</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Submit a Help Request</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Share your need with the community support network.</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Your Name <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Mobile Number <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.mobile" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.mobile" class="text-red-500 text-xs mt-1">{{ form.errors.mobile }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                                    <input type="text" v-model="form.whatsapp" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">State <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.state" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ form.errors.state }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">District <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.district" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.district" class="text-red-500 text-xs mt-1">{{ form.errors.district }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">City/Village <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.city" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                    <select v-model="form.category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="Education Help">Education Help</option>
                                        <option value="Job Help">Job Help</option>
                                        <option value="Business Help">Business Help</option>
                                        <option value="Medical Help">Medical Help</option>
                                        <option value="Emergency Help">Emergency Help</option>
                                        <option value="Government Scheme Help">Government Scheme Help</option>
                                        <option value="Legal Guidance">Legal Guidance</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ form.errors.category }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Request Title <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.title" required placeholder="Short summary of help needed" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Urgency <span class="text-red-500">*</span></label>
                                    <select v-model="form.urgency" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="Normal">Normal</option>
                                        <option value="Urgent">Urgent</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                    <div v-if="form.errors.urgency" class="text-red-500 text-xs mt-1">{{ form.errors.urgency }}</div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Detailed Description <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="5" required placeholder="Please describe exactly what kind of help you need..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-md">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="is_public" type="checkbox" v-model="form.is_public" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="is_public" class="font-medium text-gray-700">Make this request public</label>
                                        <p class="text-gray-500">If checked, this request will be visible on the public help board after admin approval. If unchecked, it will only be seen by admins and assigned volunteers.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col justify-end gap-3 border-t pt-4 sm:flex-row">
                                <Link :href="route('help.index')" class="kc-btn-secondary justify-center">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="kc-btn-primary justify-center disabled:opacity-50">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
