<script setup>
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { useLanguage } from '@/composables/useLanguage';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    states: Array
});

const { __ } = useLanguage();

const form = useForm({
    profile_photo: null,
    full_name: '',
    father_husband_name: '',
    gender: '',
    dob: '',
    mobile: '',
    whatsapp: '',
    email: '',
    state_id: '',
    district_id: '',
    city_village: '',
    pin_code: '',
    current_address: '',
    native_place: '',
    education: '',
    occupation: '',
    employment_type: '',
    company_name: '',
    skills: '',
    gotra: '',
    social_work_interest: '',
    volunteer_interest: false,
    join_district_team: false,
    facebook: '',
    instagram: '',
    linkedin: '',
    youtube: '',
    twitter: '',
    is_public: false,
    show_mobile: false,
    allow_whatsapp: true,
    consent: false,
    password: '',
    password_confirmation: ''
});

const districts = ref([]);
const errorSummary = ref(null);

watch(() => form.state_id, async (newStateId) => {
    form.district_id = '';
    districts.value = [];
    if (newStateId) {
        const response = await axios.get(`/api/states/${newStateId}/districts`);
        districts.value = response.data;
    }
});

const submit = () => {
    form.post(route('join.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            requestAnimationFrame(() => {
                errorSummary.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
    });
};
</script>

<template>
    <SeoMeta 
        title="Join the Community" 
        description="Register and become a member of the KushwahaConnect community. Connect, network, and grow together."
    />
    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Join Community</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Join Kushwaha Community</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">Create your community profile and connect with members, opportunities, support, and updates.</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="px-6 py-8 md:p-10 border-b border-gray-200 bg-gradient-to-r from-orange-50 to-white">
                        <h2 class="text-3xl font-black text-blue-950 mb-2">Create Community Profile</h2>
                        <p class="text-gray-600 text-lg">Connect with community members, get updates, and support social initiatives.</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit" class="p-6 md:p-10 space-y-8">
                        <div
                            v-if="Object.keys(form.errors).length"
                            ref="errorSummary"
                            class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
                        >
                            <p class="font-bold">Please fix the highlighted details and submit again.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                            </ul>
                        </div>

                        <!-- Personal Info -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo (Max 2MB)</label>
                                    <input type="file" @input="form.profile_photo = $event.target.files[0]" accept="image/jpeg,image/png,image/jpg" class="w-full border border-gray-300 rounded-lg shadow-sm p-2 bg-white focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.profile_photo" class="text-red-500 text-sm mt-1">{{ form.errors.profile_photo }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                                    <input v-model="form.full_name" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                    <div v-if="form.errors.full_name" class="text-red-500 text-sm mt-1">{{ form.errors.full_name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Father/Husband Name</label>
                                    <input v-model="form.father_husband_name" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.father_husband_name" class="text-red-500 text-sm mt-1">{{ form.errors.father_husband_name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                                    <select v-model="form.gender" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div v-if="form.errors.gender" class="text-red-500 text-sm mt-1">{{ form.errors.gender }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input v-model="form.dob" type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.dob" class="text-red-500 text-sm mt-1">{{ form.errors.dob }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Info -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number *</label>
                                    <input v-model="form.mobile" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                    <div v-if="form.errors.mobile" class="text-red-500 text-sm mt-1">{{ form.errors.mobile }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                                    <input v-model="form.whatsapp" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.whatsapp" class="text-red-500 text-sm mt-1">{{ form.errors.whatsapp }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input v-model="form.email" type="email" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Location</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                                    <select v-model="form.state_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                        <option value="">Select State</option>
                                        <option v-for="state in states" :key="state.id" :value="state.id">{{ state.name_en }} ({{ state.name_hi }})</option>
                                    </select>
                                    <div v-if="form.errors.state_id" class="text-red-500 text-sm mt-1">{{ form.errors.state_id }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">District *</label>
                                    <select v-model="form.district_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                        <option value="">Select District</option>
                                        <option v-for="district in districts" :key="district.id" :value="district.id">{{ district.name_en }} ({{ district.name_hi }})</option>
                                    </select>
                                    <div v-if="form.errors.district_id" class="text-red-500 text-sm mt-1">{{ form.errors.district_id }}</div>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">City/Village *</label>
                                    <input v-model="form.city_village" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                    <div v-if="form.errors.city_village" class="text-red-500 text-sm mt-1">{{ form.errors.city_village }}</div>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Address *</label>
                                    <textarea v-model="form.current_address" rows="3" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required></textarea>
                                    <div v-if="form.errors.current_address" class="text-red-500 text-sm mt-1">{{ form.errors.current_address }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pin Code *</label>
                                    <input v-model="form.pin_code" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200" required>
                                    <div v-if="form.errors.pin_code" class="text-red-500 text-sm mt-1">{{ form.errors.pin_code }}</div>
                                </div>
                            </div>
                        </div>

                            <!-- Account Security -->
                            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-sm">
                                <h3 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Account Security</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                                        <input type="password" v-model="form.password" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.password">{{ form.errors.password }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                                        <input type="password" v-model="form.password_confirmation" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                        <p class="text-xs text-red-500 mt-1" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Info -->
                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">Professional & Social</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Education</label>
                                    <input v-model="form.education" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.education" class="text-red-500 text-sm mt-1">{{ form.errors.education }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                    <input v-model="form.occupation" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                                    <div v-if="form.errors.occupation" class="text-red-500 text-sm mt-1">{{ form.errors.occupation }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy & Consent -->
                        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 space-y-4">
                            <h3 class="text-lg font-bold text-gray-800">Privacy & Consent</h3>
                            <label class="flex items-start">
                                <input v-model="form.is_public" type="checkbox" class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="ml-2 text-gray-700">Make my profile public (Visible to community members)</span>
                            </label>
                            <label class="flex items-start">
                                <input v-model="form.consent" type="checkbox" class="mt-1 rounded border-gray-300 text-orange-600 focus:ring-orange-500" required>
                                <span class="ml-2 text-sm text-gray-600">I agree that KushwahaConnect.com may use my submitted information for community connection, updates, and social communication. My personal details will not be publicly displayed without my permission. *</span>
                            </label>
                            <div v-if="form.errors.consent" class="text-red-500 text-sm">{{ form.errors.consent }}</div>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full shadow transition disabled:opacity-50">
                                {{ form.processing ? 'Submitting...' : 'Submit Details' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
