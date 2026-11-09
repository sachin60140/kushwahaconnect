<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    states: Array
});

const { __ } = useLanguage();

const form = useForm({
    photo: null,
    id_proof: null,
    profile_for: '',
    full_name: '',
    gender: '',
    dob: '',
    marital_status: '',
    mobile: '',
    email: '',
    current_city: '',
    education: '',
    occupation: '',
    consent: false,
});

const submit = () => {
    form.post(route('matrimonial.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Matrimonial - KushwahaConnect" />
    <PublicLayout :show-flash="false">
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Matrimonial Services</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Matrimonial Registration</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Create a safe, reviewed, and privacy-aware matrimonial profile for Kushwaha Samaj.</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="px-6 py-8 md:p-10 border-b border-gray-200 bg-linear-to-r from-green-800 to-blue-950 text-white">
                        <h2 class="text-3xl font-extrabold mb-2 text-white">Matrimonial Registration</h2>
                        <p class="text-green-100 text-lg">Safe and secure matrimonial profiles for Kushwaha Samaj.</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-sm shadow-xs">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit" class="p-6 md:p-10 space-y-8">
                        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            <p class="font-bold">Please fix the highlighted details and submit again.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                            </ul>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile For *</label>
                                    <select v-model="form.profile_for" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                        <option value="">Select</option>
                                        <option value="Self">Self</option>
                                        <option value="Son">Son</option>
                                        <option value="Daughter">Daughter</option>
                                        <option value="Brother">Brother</option>
                                        <option value="Sister">Sister</option>
                                        <option value="Relative">Relative</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input v-model="form.full_name" type="text" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                                    <select v-model="form.gender" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                        <option value="">Select</option>
                                        <option value="Male">Groom (Male)</option>
                                        <option value="Female">Bride (Female)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                                    <input v-model="form.dob" type="date" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Marital Status *</label>
                                    <select v-model="form.marital_status" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                        <option value="">Select</option>
                                        <option value="Never Married">Never Married</option>
                                        <option value="Divorced">Divorced</option>
                                        <option value="Widowed">Widowed</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current City</label>
                                    <input v-model="form.current_city" type="text" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Contact Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number *</label>
                                    <input v-model="form.mobile" type="text" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input v-model="form.email" type="email" class="w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Media & Documents</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Recent Photo (Max 2MB)</label>
                                    <input type="file" @input="form.photo = $event.target.files[0]" accept="image/jpeg,image/png,image/jpg" class="w-full border border-gray-300 rounded-lg shadow-xs p-2 bg-white focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                    <div v-if="form.errors.photo" class="text-red-500 text-sm mt-1">{{ form.errors.photo }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">ID Proof (Aadhar/PAN/DL) (Max 2MB)</label>
                                    <input type="file" @input="form.id_proof = $event.target.files[0]" accept="image/jpeg,image/png,image/jpg,application/pdf" class="w-full border border-gray-300 rounded-lg shadow-xs p-2 bg-white focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                    <div v-if="form.errors.id_proof" class="text-red-500 text-sm mt-1">{{ form.errors.id_proof }}</div>
                                    <p class="text-xs text-gray-500 mt-1">Stored securely and visible only to Admins.</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 p-6 rounded-lg border border-green-200 space-y-4">
                            <h3 class="text-lg font-bold text-gray-800">Privacy & Consent</h3>
                            <label class="flex items-start">
                                <input v-model="form.consent" type="checkbox" class="mt-1 rounded-sm border-gray-300 text-green-600 focus:ring-green-500" required>
                                <span class="ml-2 text-sm text-gray-600">I agree to submit this matrimonial profile. I understand it will be verified by the admin before being published. Mobile numbers and contact details will be kept private. *</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-full shadow-sm transition disabled:opacity-50">
                                Register Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
