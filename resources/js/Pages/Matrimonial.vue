<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    states: Array,
});

// Complete profile, matching MatrimonialProfile::detailRules() on the server.
const sections = [
    {
        title: 'Basic Information',
        fields: [
            { key: 'profile_for', label: 'Profile For', type: 'select', required: true, options: ['Self', 'Son', 'Daughter', 'Brother', 'Sister', 'Relative', 'Friend'] },
            { key: 'full_name', label: 'Full Name', required: true },
            { key: 'gender', label: 'Gender', type: 'select', required: true, options: [{ value: 'Male', label: 'Groom (Male)' }, { value: 'Female', label: 'Bride (Female)' }] },
            { key: 'dob', label: 'Date of Birth', type: 'date', required: true },
            { key: 'marital_status', label: 'Marital Status', type: 'select', required: true, options: ['Never Married', 'Awaiting Divorce', 'Divorced', 'Widowed', 'Annulled'] },
            { key: 'height', label: 'Height', required: true, placeholder: "e.g. 5' 6\"" },
            { key: 'manglik', label: 'Manglik Status', type: 'select', options: ['No', 'Yes', "Don't Know", 'Anshik'] },
            { key: 'gotra', label: 'Gotra / Sub-caste' },
            { key: 'mother_tongue', label: 'Mother Tongue', placeholder: 'e.g. Hindi, Bhojpuri' },
            { key: 'current_city', label: 'Current City' },
            { key: 'native_place', label: 'Native Place' },
        ],
    },
    {
        title: 'Education & Career',
        fields: [
            { key: 'education', label: 'Highest Education', required: true, placeholder: 'e.g. B.Tech, M.Sc.' },
            { key: 'college', label: 'College / University' },
            { key: 'occupation', label: 'Occupation / Profession', required: true },
            { key: 'company', label: 'Company / Organisation' },
            { key: 'annual_income', label: 'Annual Income', placeholder: 'e.g. 5-7 Lakhs' },
            { key: 'work_location', label: 'Work Location (City)' },
        ],
    },
    {
        title: 'Family Details',
        fields: [
            { key: 'father_name', label: "Father's Name", required: true },
            { key: 'father_occupation', label: "Father's Occupation" },
            { key: 'mother_name', label: "Mother's Name" },
            { key: 'mother_occupation', label: "Mother's Occupation" },
            { key: 'brothers', label: 'Number of Brothers', type: 'number' },
            { key: 'sisters', label: 'Number of Sisters', type: 'number' },
            { key: 'family_type', label: 'Family Type', type: 'select', options: [{ value: 'Joint', label: 'Joint Family' }, { value: 'Nuclear', label: 'Nuclear Family' }, { value: 'Others', label: 'Others' }] },
            { key: 'family_location', label: 'Family Location (City/Town)', required: true },
        ],
    },
    {
        title: 'Partner Preferences',
        fields: [
            { key: 'pref_age_range', label: 'Preferred Age Range', placeholder: 'e.g. 24-28' },
            { key: 'pref_height', label: 'Preferred Height', placeholder: "e.g. 5'2\" to 5'6\"" },
            { key: 'pref_education', label: 'Preferred Education' },
            { key: 'pref_profession', label: 'Preferred Profession' },
            { key: 'pref_location', label: 'Preferred Location', span: true },
            { key: 'other_expectations', label: 'Other Expectations', type: 'textarea', span: true },
        ],
    },
    {
        title: 'Contact Details',
        fields: [
            { key: 'mobile', label: 'Mobile Number', type: 'tel', required: true },
            { key: 'whatsapp', label: 'WhatsApp Number', type: 'tel' },
            { key: 'email', label: 'Email', type: 'email', span: true },
        ],
    },
];

const initial = Object.fromEntries(sections.flatMap((section) => section.fields.map((field) => [field.key, ''])));

const form = useForm({
    ...initial,
    manglik: 'No',
    brothers: 0,
    sisters: 0,
    photo: null,
    id_proof: null,
    consent: false,
});

const optionValue = (option) => (typeof option === 'string' ? option : option.value);
const optionLabel = (option) => (typeof option === 'string' ? option : option.label);

const submit = () => {
    form.post(route('matrimonial.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const inputClass = 'w-full border-gray-300 rounded-lg shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200';
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
                        <p class="text-green-100 text-lg">Please fill in complete details. Fields marked * are required.</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-sm shadow-xs">
                        {{ $page.props.flash.success }}
                        <p class="mt-1 text-sm">Members can add a biodata from <strong>My Matrimonial Profile</strong> after logging in.</p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 md:p-10 space-y-8">
                        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            <p class="font-bold">Please fix the highlighted details and submit again.</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                            </ul>
                        </div>

                        <div v-for="section in sections" :key="section.title">
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">{{ section.title }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="field in section.fields" :key="field.key" :class="{ 'md:col-span-2': field.span }">
                                    <label :for="field.key" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ field.label }}<span v-if="field.required" class="text-red-500"> *</span>
                                    </label>

                                    <select v-if="field.type === 'select'" :id="field.key" v-model="form[field.key]" :required="field.required" :class="inputClass">
                                        <option value="">Select</option>
                                        <option v-for="option in field.options" :key="optionValue(option)" :value="optionValue(option)">{{ optionLabel(option) }}</option>
                                    </select>
                                    <textarea v-else-if="field.type === 'textarea'" :id="field.key" v-model="form[field.key]" rows="3" :class="inputClass"></textarea>
                                    <input
                                        v-else
                                        :id="field.key"
                                        v-model="form[field.key]"
                                        :type="field.type || 'text'"
                                        :min="field.type === 'number' ? 0 : undefined"
                                        :max="field.type === 'number' ? 20 : undefined"
                                        :required="field.required"
                                        :placeholder="field.placeholder"
                                        :class="inputClass"
                                    >
                                    <div v-if="form.errors[field.key]" class="text-red-500 text-sm mt-1">{{ form.errors[field.key] }}</div>
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
