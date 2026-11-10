<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useLanguage } from '@/composables/useLanguage';
import { VOLUNTEER_AREAS, VOLUNTEER_TIMES } from '@/utils/optionLabels';

const { __ } = useLanguage();
const page = usePage();
const user = computed(() => page.props.auth?.user);

const form = useForm({
    name: user.value ? user.value.name : '',
    mobile: user.value ? user.value.mobile : '',
    whatsapp: '',
    email: user.value ? user.value.email : '',
    state: '',
    district: '',
    city: '',
    skills: '',
    available_time: '',
    help_areas: [],
    consent: false
});

const submit = () => {
    form.post(route('volunteer.store'));
};

const inputClass = 'block w-full rounded-lg border-gray-300 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200';
</script>

<template>
    <Head title="Become a Volunteer" />

    <component :is="user ? AuthenticatedLayout : PublicLayout" :show-flash="false">
        <template #header v-if="user">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">{{ __('vol_network') }}</p>
                <h2 class="mt-1 text-2xl font-black text-blue-950">{{ __('vol_become_title') }}</h2>
            </div>
        </template>
        <template #header v-else>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('vol_network') }}</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('vol_become_title') }}</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('vol_create_intro') }}</p>
                </div>
                <Link href="/" class="kc-btn-secondary shrink-0">{{ __('vol_back_home') }}</Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden border-t-4 border-orange-500">
                    <div class="p-8 text-gray-900">
                        <div v-if="!user" class="mb-8 text-center border-b border-gray-200 pb-6">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ __('vol_card_title') }}</h2>
                            <p class="text-gray-600">{{ __('vol_card_intro') }}</p>
                        </div>

                        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-sm shadow-xs">
                            {{ __($page.props.flash.success) }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">

                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2">{{ __('vol_personal_info') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="vol-name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common_full_name') }} <span class="text-red-500">*</span></label>
                                    <input id="vol-name" type="text" v-model="form.name" required :class="inputClass">
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ __(form.errors.name) }}</div>
                                </div>
                                <div>
                                    <label for="vol-email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vol_email') }}</label>
                                    <input id="vol-email" type="email" v-model="form.email" :class="inputClass">
                                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ __(form.errors.email) }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="vol-mobile" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common_mobile_number') }} <span class="text-red-500">*</span></label>
                                    <input id="vol-mobile" type="text" v-model="form.mobile" required :class="inputClass">
                                    <MobileOtpField :mobile="form.mobile" :error="form.errors.mobile" id-prefix="volunteer-mobile-otp" />
                                </div>
                                <div>
                                    <label for="vol-whatsapp" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common_whatsapp_number') }}</label>
                                    <input id="vol-whatsapp" type="text" v-model="form.whatsapp" :class="inputClass">
                                    <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ __(form.errors.whatsapp) }}</div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2 pt-4">{{ __('vol_location_details') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="vol-state" class="block text-sm font-medium text-gray-700 mb-1">{{ __('join_state') }} <span class="text-red-500">*</span></label>
                                    <input id="vol-state" type="text" v-model="form.state" required :class="inputClass">
                                    <div v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ __(form.errors.state) }}</div>
                                </div>
                                <div>
                                    <label for="vol-district" class="block text-sm font-medium text-gray-700 mb-1">{{ __('join_district') }} <span class="text-red-500">*</span></label>
                                    <input id="vol-district" type="text" v-model="form.district" required :class="inputClass">
                                    <div v-if="form.errors.district" class="text-red-500 text-xs mt-1">{{ __(form.errors.district) }}</div>
                                </div>
                                <div>
                                    <label for="vol-city" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common_city_village') }} <span class="text-red-500">*</span></label>
                                    <input id="vol-city" type="text" v-model="form.city" required :class="inputClass">
                                    <div v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ __(form.errors.city) }}</div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2 pt-4">{{ __('vol_skills_availability') }}</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="vol-skills" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vol_your_skills') }}</label>
                                    <input id="vol-skills" type="text" v-model="form.skills" :placeholder="__('vol_skills_placeholder')" :class="inputClass">
                                    <div v-if="form.errors.skills" class="text-red-500 text-xs mt-1">{{ __(form.errors.skills) }}</div>
                                </div>
                                <div>
                                    <label for="vol-time" class="block text-sm font-medium text-gray-700 mb-1">{{ __('vol_available_time') }}</label>
                                    <select id="vol-time" v-model="form.available_time" :class="inputClass">
                                        <option value="">{{ __('vol_select_availability') }}</option>
                                        <option v-for="option in VOLUNTEER_TIMES" :key="option.value" :value="option.value">{{ __(option.labelKey) }}</option>
                                    </select>
                                    <div v-if="form.errors.available_time" class="text-red-500 text-xs mt-1">{{ __(form.errors.available_time) }}</div>
                                </div>
                            </div>

                            <fieldset>
                                <legend class="block text-sm font-medium text-gray-700 mb-2">{{ __('vol_areas_label') }}</legend>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 md:grid-cols-3 md:gap-4">
                                    <label v-for="option in VOLUNTEER_AREAS" :key="option.value" class="flex min-h-11 cursor-pointer items-center rounded-lg px-2 hover:bg-gray-50">
                                        <input type="checkbox" v-model="form.help_areas" :value="option.value" class="h-5 w-5 shrink-0 rounded-sm border-gray-300 text-green-600 shadow-xs focus:border-green-500 focus:ring-3 focus:ring-green-200">
                                        <span class="ml-3 text-sm text-gray-600">{{ __(option.labelKey) }}</span>
                                    </label>
                                </div>
                            </fieldset>

                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mt-6">
                                <label class="flex min-h-11 cursor-pointer items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" v-model="form.consent" required class="focus:ring-3 focus:ring-green-200 h-5 w-5 text-green-600 border-gray-300 rounded-sm">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-medium text-gray-700">
                                            {{ __('vol_agree_terms').split(':terms')[0] }}<Link href="/terms" class="text-green-600 underline">{{ __('vol_terms_link') }}</Link>{{ __('vol_agree_terms').split(':terms')[1] }}
                                        </span>
                                        <p class="text-gray-500 text-xs mt-1">{{ __('vol_consent_hint') }}</p>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 border-t border-gray-200 pt-6 mt-6">
                                <Link :href="route('welcome')" class="inline-flex min-h-11 items-center bg-white border border-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded-lg shadow-xs hover:bg-gray-50 transition-colors duration-200">{{ __('common_cancel') }}</Link>
                                <button type="submit" :disabled="form.processing" class="min-h-11 bg-orange-500 text-white font-bold px-6 py-2.5 rounded-lg shadow-sm hover:bg-orange-600 disabled:opacity-50 transition-colors duration-200">{{ __('vol_submit') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
