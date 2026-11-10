<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useLanguage } from '@/composables/useLanguage';
import { MAT_FAMILY_TYPES, MAT_GENDERS, MAT_MANGLIK, MAT_MARITAL_STATUSES, MAT_PROFILE_FOR } from '@/utils/optionLabels';

const { __ } = useLanguage();

defineProps({
    states: Array,
});

// Complete profile, matching MatrimonialProfile::detailRules() on the server.
// Option `value`s are what the server validates; only their labels are translated.
const sections = [
    {
        titleKey: 'mat_section_basic',
        fields: [
            { key: 'profile_for', labelKey: 'mat_profile_for', type: 'select', required: true, options: MAT_PROFILE_FOR },
            { key: 'full_name', labelKey: 'common_full_name', required: true },
            { key: 'gender', labelKey: 'join_gender', type: 'select', required: true, options: MAT_GENDERS },
            { key: 'dob', labelKey: 'mat_dob', type: 'date', required: true },
            { key: 'marital_status', labelKey: 'mat_marital_status', type: 'select', required: true, options: MAT_MARITAL_STATUSES },
            { key: 'height', labelKey: 'mat_height', required: true, placeholderKey: 'mat_height_placeholder' },
            { key: 'manglik', labelKey: 'mat_manglik', type: 'select', options: MAT_MANGLIK },
            { key: 'gotra', labelKey: 'mat_gotra' },
            { key: 'mother_tongue', labelKey: 'mat_mother_tongue', placeholderKey: 'mat_mother_tongue_placeholder' },
            { key: 'current_city', labelKey: 'mat_current_city' },
            { key: 'native_place', labelKey: 'mat_native_place' },
        ],
    },
    {
        titleKey: 'mat_section_career',
        fields: [
            { key: 'education', labelKey: 'mat_education', required: true, placeholderKey: 'mat_education_placeholder' },
            { key: 'college', labelKey: 'mat_college' },
            { key: 'occupation', labelKey: 'mat_occupation', required: true },
            { key: 'company', labelKey: 'mat_company' },
            { key: 'annual_income', labelKey: 'mat_annual_income', placeholderKey: 'mat_annual_income_placeholder' },
            { key: 'work_location', labelKey: 'mat_work_location' },
        ],
    },
    {
        titleKey: 'mat_section_family',
        fields: [
            { key: 'father_name', labelKey: 'mat_father_name', required: true },
            { key: 'father_occupation', labelKey: 'mat_father_occupation' },
            { key: 'mother_name', labelKey: 'mat_mother_name' },
            { key: 'mother_occupation', labelKey: 'mat_mother_occupation' },
            { key: 'brothers', labelKey: 'mat_brothers', type: 'number' },
            { key: 'sisters', labelKey: 'mat_sisters', type: 'number' },
            { key: 'family_type', labelKey: 'mat_family_type', type: 'select', options: MAT_FAMILY_TYPES },
            { key: 'family_location', labelKey: 'mat_family_location', required: true },
        ],
    },
    {
        titleKey: 'mat_section_partner',
        fields: [
            { key: 'pref_age_range', labelKey: 'mat_pref_age', placeholderKey: 'mat_pref_age_placeholder' },
            { key: 'pref_height', labelKey: 'mat_pref_height', placeholderKey: 'mat_pref_height_placeholder' },
            { key: 'pref_education', labelKey: 'mat_pref_education' },
            { key: 'pref_profession', labelKey: 'mat_pref_profession' },
            { key: 'pref_location', labelKey: 'mat_pref_location', span: true },
            { key: 'other_expectations', labelKey: 'mat_other_expectations', type: 'textarea', span: true },
        ],
    },
    {
        titleKey: 'mat_section_contact',
        fields: [
            { key: 'mobile', labelKey: 'common_mobile_number', type: 'tel', required: true },
            { key: 'whatsapp', labelKey: 'common_whatsapp_number', type: 'tel' },
            { key: 'email', labelKey: 'join_email', type: 'email', span: true },
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
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('mat_eyebrow') }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('btn_matrimonial') }}</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('mat_intro') }}</p>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="px-6 py-8 md:p-10 border-b border-gray-200 bg-linear-to-r from-green-800 to-blue-950 text-white">
                        <h2 class="text-3xl font-extrabold mb-2 text-white">{{ __('btn_matrimonial') }}</h2>
                        <p class="text-green-100 text-lg">{{ __('mat_form_intro') }}</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-sm shadow-xs">
                        {{ __($page.props.flash.success) }}
                        <p class="mt-1 text-sm">
                            {{ __('mat_success_note').split(':page')[0] }}<strong>{{ __('mat_my_profile') }}</strong>{{ __('mat_success_note').split(':page')[1] }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="p-6 md:p-10 space-y-8">
                        <div v-if="Object.keys(form.errors).length" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
                            <p class="font-bold">{{ __('join_fix_errors') }}</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                <li v-for="(message, field) in form.errors" :key="field">{{ __(message) }}</li>
                            </ul>
                        </div>

                        <div v-for="section in sections" :key="section.titleKey">
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">{{ __(section.titleKey) }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-for="field in section.fields" :key="field.key" :class="{ 'md:col-span-2': field.span }">
                                    <label :for="field.key" class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ __(field.labelKey) }}<span v-if="field.required" class="text-red-500"> *</span>
                                    </label>

                                    <select v-if="field.type === 'select'" :id="field.key" v-model="form[field.key]" :required="field.required" :class="inputClass">
                                        <option value="">{{ __('join_select') }}</option>
                                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
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
                                        :placeholder="field.placeholderKey ? __(field.placeholderKey) : undefined"
                                        :class="inputClass"
                                    >
                                    <MobileOtpField v-if="field.key === 'mobile'" :mobile="form.mobile" :error="form.errors.mobile" id-prefix="matrimonial-mobile-otp" />
                                    <div v-else-if="form.errors[field.key]" class="text-red-500 text-sm mt-1">{{ __(form.errors[field.key]) }}</div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-xl font-bold text-gray-800 border-b pb-2 mb-4">{{ __('mat_section_media') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="mat-photo" class="block text-sm font-medium text-gray-700 mb-1">{{ __('mat_photo') }}</label>
                                    <input id="mat-photo" type="file" @input="form.photo = $event.target.files[0]" accept="image/jpeg,image/png,image/jpg" class="min-h-11 w-full border border-gray-300 rounded-lg shadow-xs p-2 bg-white focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                    <div v-if="form.errors.photo" class="text-red-500 text-sm mt-1">{{ __(form.errors.photo) }}</div>
                                </div>
                                <div>
                                    <label for="mat-id-proof" class="block text-sm font-medium text-gray-700 mb-1">{{ __('mat_id_proof') }}</label>
                                    <input id="mat-id-proof" type="file" @input="form.id_proof = $event.target.files[0]" accept="image/jpeg,image/png,image/jpg,application/pdf" class="min-h-11 w-full border border-gray-300 rounded-lg shadow-xs p-2 bg-white focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                    <div v-if="form.errors.id_proof" class="text-red-500 text-sm mt-1">{{ __(form.errors.id_proof) }}</div>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('mat_id_proof_hint') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-green-50 p-6 rounded-lg border border-green-200 space-y-4">
                            <h3 class="text-lg font-bold text-gray-800">{{ __('mat_privacy_title') }}</h3>
                            <label class="flex min-h-11 cursor-pointer items-start gap-3 py-2">
                                <input v-model="form.consent" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 rounded-sm border-gray-300 text-green-600 focus:ring-green-500" required>
                                <span class="text-sm text-gray-600">{{ __('mat_consent') }} *</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end">
                            <button type="submit" :disabled="form.processing" class="w-full sm:w-auto bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-full shadow-sm transition disabled:opacity-50">
                                {{ __('mat_submit') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
