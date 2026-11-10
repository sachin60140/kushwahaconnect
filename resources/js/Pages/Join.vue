<script setup>
// Quick sign-up: name, mobile (+ OTP), gender, state, district and consent.
// Everything else (photo, address, DOB, WhatsApp, ...) is added later from
// "My community profile"; the dashboard lists what is still missing.
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { useLanguage } from '@/composables/useLanguage';
import { computed, ref, watch } from 'vue';
import axios from 'axios';

defineProps({
    states: Array,
});

const { __, locale } = useLanguage();
const page = usePage();

// Without SMS there is no OTP login, so a password is needed to sign in again.
const passwordRequired = computed(() => !page.props.mobileOtp?.required);

const form = useForm({
    full_name: '',
    gender: '',
    mobile: '',
    state_id: '',
    district_id: '',
    email: '',
    is_public: false,
    consent: false,
    password: '',
    password_confirmation: '',
});

const showMore = ref(false);
const showPassword = ref(false);
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

// Reveal optional sections that came back with errors.
watch(() => form.errors, (errors) => {
    if (errors.email) showMore.value = true;
    if (errors.password || errors.password_confirmation) showPassword.value = true;
});

const placeName = (item) => (locale.value === 'hi' && item.name_hi ? item.name_hi : item.name_en);

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

const inputClass = 'block min-h-11 w-full rounded-lg border-gray-300 px-3 py-2 text-base shadow-xs focus:border-orange-500 focus:ring-3 focus:ring-orange-200';
const labelClass = 'mb-1 block text-base font-semibold text-gray-800';
const errorClass = 'mt-1 text-sm font-semibold text-red-600';
</script>

<template>
    <SeoMeta
        title="Join the Community"
        description="Register and become a member of the KushwahaConnect community. Connect, network, and grow together."
    />
    <PublicLayout :show-flash="false">
        <template #header>
            <div class="max-w-3xl">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Join Community</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-white">{{ __('join_title') }}</h1>
                <p class="mt-4 text-lg leading-8 text-blue-100">{{ __('join_intro') }}</p>
            </div>
        </template>

        <div class="bg-gray-50 py-8 sm:py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden">
                    <div class="border-b border-gray-200 bg-linear-to-r from-orange-50 to-white px-4 py-6 sm:px-8">
                        <h2 class="text-2xl font-black text-blue-950 sm:text-3xl">{{ __('join_card_title') }}</h2>
                        <p class="mt-1 text-base text-gray-600">{{ __('join_card_intro') }}</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-4 rounded-sm border-l-4 border-green-500 bg-green-50 p-4 text-green-700 shadow-xs">
                        {{ __($page.props.flash.success) }}
                    </div>

                    <form class="space-y-5 px-4 py-6 sm:px-8" @submit.prevent="submit">
                        <div
                            v-if="Object.keys(form.errors).length"
                            ref="errorSummary"
                            class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-700"
                            role="alert"
                        >
                            <p class="font-bold">{{ __('join_fix_errors') }}</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                                <li v-for="(message, field) in form.errors" :key="field">{{ __(message) }}</li>
                            </ul>
                        </div>

                        <div>
                            <label for="join-name" :class="labelClass">{{ __('join_full_name') }} *</label>
                            <input id="join-name" v-model="form.full_name" type="text" autocomplete="name" required :class="inputClass">
                            <p v-if="form.errors.full_name" :class="errorClass">{{ __(form.errors.full_name) }}</p>
                        </div>

                        <div>
                            <label for="join-mobile" :class="labelClass">{{ __('join_mobile') }} *</label>
                            <input id="join-mobile" v-model="form.mobile" type="tel" inputmode="numeric" autocomplete="tel-national" required :class="inputClass">
                            <MobileOtpField :mobile="form.mobile" :error="form.errors.mobile" id-prefix="join-mobile-otp" />
                        </div>

                        <div>
                            <span :class="labelClass">{{ __('join_gender') }} *</span>
                            <div class="grid grid-cols-3 gap-2" role="radiogroup">
                                <label
                                    v-for="option in ['Male', 'Female', 'Other']"
                                    :key="option"
                                    class="flex min-h-11 cursor-pointer items-center justify-center rounded-lg border px-2 text-base font-semibold"
                                    :class="form.gender === option ? 'border-orange-500 bg-orange-50 text-orange-800' : 'border-gray-300 bg-white text-gray-700'"
                                >
                                    <input v-model="form.gender" type="radio" name="gender" :value="option" class="sr-only" required>
                                    {{ __(`join_gender_${option.toLowerCase()}`) }}
                                </label>
                            </div>
                            <p v-if="form.errors.gender" :class="errorClass">{{ __(form.errors.gender) }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="join-state" :class="labelClass">{{ __('join_state') }} *</label>
                                <select id="join-state" v-model="form.state_id" required :class="inputClass">
                                    <option value="">{{ __('join_select') }}</option>
                                    <option v-for="state in states" :key="state.id" :value="state.id">{{ placeName(state) }}</option>
                                </select>
                                <p v-if="form.errors.state_id" :class="errorClass">{{ __(form.errors.state_id) }}</p>
                            </div>
                            <div>
                                <label for="join-district" :class="labelClass">{{ __('join_district') }} *</label>
                                <select id="join-district" v-model="form.district_id" required :disabled="!form.state_id" :class="[inputClass, 'disabled:bg-gray-100']">
                                    <option value="">{{ __('join_select') }}</option>
                                    <option v-for="district in districts" :key="district.id" :value="district.id">{{ placeName(district) }}</option>
                                </select>
                                <p v-if="form.errors.district_id" :class="errorClass">{{ __(form.errors.district_id) }}</p>
                            </div>
                        </div>

                        <!-- Optional: email -->
                        <div>
                            <button
                                v-if="!showMore"
                                type="button"
                                class="min-h-11 text-base font-bold text-orange-700 underline"
                                @click="showMore = true"
                            >
                                + {{ __('join_more_details') }}
                            </button>
                            <div v-else>
                                <label for="join-email" :class="labelClass">{{ __('join_email') }}</label>
                                <input id="join-email" v-model="form.email" type="email" autocomplete="email" :class="inputClass">
                                <p v-if="form.errors.email" :class="errorClass">{{ __(form.errors.email) }}</p>
                            </div>
                        </div>

                        <!-- Password: optional while mobile OTP login is available -->
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <template v-if="!passwordRequired && !showPassword">
                                <p class="text-sm text-gray-600">{{ __('join_password_optional_hint') }}</p>
                                <button type="button" class="mt-1 min-h-11 text-base font-bold text-orange-700 underline" @click="showPassword = true">
                                    + {{ __('join_set_password') }}
                                </button>
                            </template>
                            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <p v-if="passwordRequired" class="text-sm text-gray-600 sm:col-span-2">{{ __('join_password_required_hint') }}</p>
                                <div>
                                    <label for="join-password" :class="labelClass">{{ __('join_password') }}<template v-if="passwordRequired"> *</template></label>
                                    <input id="join-password" v-model="form.password" type="password" autocomplete="new-password" :required="passwordRequired" :class="inputClass">
                                    <p v-if="form.errors.password" :class="errorClass">{{ __(form.errors.password) }}</p>
                                </div>
                                <div>
                                    <label for="join-password-confirm" :class="labelClass">{{ __('join_password_confirm') }}<template v-if="passwordRequired"> *</template></label>
                                    <input id="join-password-confirm" v-model="form.password_confirmation" type="password" autocomplete="new-password" :required="passwordRequired || !!form.password" :class="inputClass">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex min-h-11 items-start gap-3">
                                <input v-model="form.is_public" type="checkbox" class="mt-1 h-5 w-5 rounded-sm border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-base text-gray-700">{{ __('join_is_public') }}</span>
                            </label>
                            <label class="flex min-h-11 items-start gap-3">
                                <input v-model="form.consent" type="checkbox" required class="mt-1 h-5 w-5 rounded-sm border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm text-gray-600">{{ __('join_consent') }} *</span>
                            </label>
                            <p v-if="form.errors.consent" :class="errorClass">{{ __(form.errors.consent) }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex min-h-12 w-full items-center justify-center rounded-full bg-green-600 px-8 py-3 text-lg font-bold text-white shadow-sm transition hover:bg-green-700 disabled:opacity-50"
                        >
                            {{ form.processing ? __('join_submitting') : __('join_submit') }}
                        </button>
                        <p class="text-center text-sm text-gray-500">{{ __('join_later_hint') }}</p>
                    </form>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
