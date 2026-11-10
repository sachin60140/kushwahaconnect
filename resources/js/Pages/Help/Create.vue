<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import HelpExtraFields from '@/Components/HelpExtraFields.vue';
import { useLanguage } from '@/composables/useLanguage';
import { HELP_CATEGORIES, HELP_URGENCIES } from '@/utils/optionLabels';

const { __ } = useLanguage();
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
    is_public: true,
    // Optional supporting documents & donation details (shown after admin approval).
    documents: [],
    account_holder_name: '',
    bank_account_number: '',
    bank_ifsc: '',
    bank_name: '',
    bank_branch: '',
    upi_id: '',
    upi_qr: null,
});

const submit = () => {
    form.post(route('help.store'), { forceFormData: true, preserveScroll: 'errors' });
};
</script>

<template>
    <Head title="Request Help" />

    <PublicLayout>
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('help_eyebrow') }}</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('help_create_title') }}</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('help_create_intro') }}</p>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="help-name" class="block text-sm font-medium text-gray-700">{{ __('help_your_name') }} <span class="text-red-500">*</span></label>
                                    <input id="help-name" type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ __(form.errors.name) }}</div>
                                </div>
                                <div>
                                    <label for="help-mobile" class="block text-sm font-medium text-gray-700">{{ __('common_mobile_number') }} <span class="text-red-500">*</span></label>
                                    <input id="help-mobile" type="text" v-model="form.mobile" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <MobileOtpField :mobile="form.mobile" :error="form.errors.mobile" id-prefix="help-mobile-otp" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="help-whatsapp" class="block text-sm font-medium text-gray-700">{{ __('common_whatsapp_number') }}</label>
                                    <input id="help-whatsapp" type="text" v-model="form.whatsapp" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ __(form.errors.whatsapp) }}</div>
                                </div>
                                <div>
                                    <label for="help-state" class="block text-sm font-medium text-gray-700">{{ __('join_state') }} <span class="text-red-500">*</span></label>
                                    <input id="help-state" type="text" v-model="form.state" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ __(form.errors.state) }}</div>
                                </div>
                                <div>
                                    <label for="help-district" class="block text-sm font-medium text-gray-700">{{ __('join_district') }} <span class="text-red-500">*</span></label>
                                    <input id="help-district" type="text" v-model="form.district" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.district" class="text-red-500 text-xs mt-1">{{ __(form.errors.district) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="help-city" class="block text-sm font-medium text-gray-700">{{ __('common_city_village') }} <span class="text-red-500">*</span></label>
                                    <input id="help-city" type="text" v-model="form.city" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ __(form.errors.city) }}</div>
                                </div>
                                <div>
                                    <label for="help-category" class="block text-sm font-medium text-gray-700">{{ __('common_category') }} <span class="text-red-500">*</span></label>
                                    <select id="help-category" v-model="form.category" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option v-for="option in HELP_CATEGORIES" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                                    </select>
                                    <div v-if="form.errors.category" class="text-red-500 text-xs mt-1">{{ __(form.errors.category) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="help-title" class="block text-sm font-medium text-gray-700">{{ __('help_request_title') }} <span class="text-red-500">*</span></label>
                                    <input id="help-title" type="text" v-model="form.title" required :placeholder="__('help_request_title_placeholder')" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ __(form.errors.title) }}</div>
                                </div>
                                <div>
                                    <label for="help-urgency" class="block text-sm font-medium text-gray-700">{{ __('help_urgency') }} <span class="text-red-500">*</span></label>
                                    <select id="help-urgency" v-model="form.urgency" required class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option v-for="option in HELP_URGENCIES" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                                    </select>
                                    <div v-if="form.errors.urgency" class="text-red-500 text-xs mt-1">{{ __(form.errors.urgency) }}</div>
                                </div>
                            </div>

                            <div>
                                <label for="help-description" class="block text-sm font-medium text-gray-700">{{ __('help_description') }} <span class="text-red-500">*</span></label>
                                <textarea id="help-description" v-model="form.description" rows="5" required maxlength="10000" :placeholder="__('help_description_placeholder')" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ __(form.errors.description) }}</div>
                            </div>

                            <HelpExtraFields :form="form" class="border-t pt-6" />

                            <div class="bg-gray-50 p-4 rounded-md">
                                <label for="is_public" class="flex min-h-11 cursor-pointer items-start gap-3">
                                    <input id="is_public" type="checkbox" v-model="form.is_public" class="mt-0.5 h-5 w-5 shrink-0 focus:ring-indigo-500 text-indigo-600 border-gray-300 rounded-sm">
                                    <span class="text-sm">
                                        <span class="font-medium text-gray-700">{{ __('help_make_public') }}</span>
                                        <span class="block text-gray-500">{{ __('help_make_public_hint') }}</span>
                                    </span>
                                </label>
                            </div>

                            <div class="flex flex-col justify-end gap-3 border-t pt-4 sm:flex-row">
                                <Link :href="route('help.index')" class="kc-btn-secondary justify-center">{{ __('common_cancel') }}</Link>
                                <button type="submit" :disabled="form.processing" class="kc-btn-primary justify-center disabled:opacity-50">{{ __('help_submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
