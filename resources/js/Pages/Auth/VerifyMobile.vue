<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    mobile: { type: String, default: null },
    verified: { type: Boolean, default: false },
    // { resend_in: seconds, expires_in: seconds } for the current code, if any.
    otp: { type: Object, default: null },
    codeLength: { type: Number, default: 6 },
});

const { __ } = useLanguage();

const sendForm = useForm({});
const verifyForm = useForm({ code: '' });

const resendIn = ref(0);
const expiresIn = ref(0);
// A code was sent during this visit, so keep the entry box open even after it expires.
const codeRequested = ref(false);
let timer = null;

const syncTimers = () => {
    resendIn.value = props.otp?.resend_in ?? 0;
    expiresIn.value = props.otp?.expires_in ?? 0;
    if (expiresIn.value > 0) {
        codeRequested.value = true;
    }
};

watch(() => props.otp, syncTimers, { immediate: true });

onMounted(() => {
    timer = setInterval(() => {
        if (resendIn.value > 0) resendIn.value -= 1;
        if (expiresIn.value > 0) expiresIn.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => clearInterval(timer));

const showCodeEntry = computed(() => codeRequested.value);
const codeExpired = computed(() => codeRequested.value && expiresIn.value <= 0);

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = String(seconds % 60).padStart(2, '0');
    return `${m}:${s}`;
};

const onCodeInput = (event) => {
    verifyForm.code = event.target.value.replace(/\D/g, '').slice(0, props.codeLength);
    verifyForm.clearErrors('code');
};

const sendCode = () => {
    sendForm.post(route('mobile.verify.send'), {
        preserveScroll: true,
        onSuccess: () => {
            verifyForm.reset('code');
            verifyForm.clearErrors();
        },
    });
};

const verifyCode = () => {
    verifyForm.post(route('mobile.verify'), {
        preserveScroll: true,
        onError: () => verifyForm.reset('code'),
    });
};
</script>

<template>
    <Head :title="__('otp_page_title')" />

    <AuthenticatedLayout>
        <div class="px-4 py-8 sm:py-12">
            <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5h3a2.25 2.25 0 0 1 2.25 2.25v16.5a2.25 2.25 0 0 1-2.25 2.25h-3a2.25 2.25 0 0 1-2.25-2.25V3.75A2.25 2.25 0 0 1 10.5 1.5ZM12 18.75h.008" />
                        </svg>
                    </div>
                    <h1 class="text-xl font-black leading-tight text-blue-950 sm:text-2xl">{{ __('otp_page_title') }}</h1>
                </div>

                <!-- Already verified -->
                <div v-if="verified" class="space-y-5">
                    <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-base font-semibold text-green-800">
                        {{ __('otp_already_verified') }}
                        <span v-if="mobile" class="block font-mono text-sm text-green-700">{{ mobile }}</span>
                    </p>
                    <Link :href="route('dashboard')" class="flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-950 px-4 py-3 text-base font-bold text-white hover:bg-blue-900">
                        {{ __('otp_go_dashboard') }}
                    </Link>
                </div>

                <!-- No mobile on file -->
                <div v-else-if="!mobile" class="space-y-5">
                    <p class="text-base text-slate-600">{{ __('otp_no_mobile') }}</p>
                    <Link :href="route('user.member-profile.edit')" class="flex min-h-11 w-full items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-base font-bold text-white hover:bg-orange-500">
                        {{ __('otp_add_mobile') }}
                    </Link>
                </div>

                <div v-else class="space-y-5">
                    <p class="text-base text-slate-600">
                        {{ __('otp_page_intro') }}
                        <span class="mt-1 block font-mono text-lg font-bold tracking-wider text-slate-900">{{ mobile }}</span>
                    </p>

                    <p v-if="sendForm.errors.otp" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">
                        {{ __(sendForm.errors.otp) }}
                    </p>

                    <!-- Step 1: request a code -->
                    <button
                        v-if="!showCodeEntry"
                        type="button"
                        class="flex min-h-11 w-full items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-base font-bold text-white hover:bg-orange-500 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="sendForm.processing || resendIn > 0"
                        @click="sendCode"
                    >
                        <span v-if="sendForm.processing">{{ __('otp_sending') }}</span>
                        <span v-else-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</span>
                        <span v-else>{{ __('otp_send_button') }}</span>
                    </button>

                    <!-- Step 2: enter the code -->
                    <form v-else class="space-y-4" @submit.prevent="verifyCode">
                        <div>
                            <label for="otp-code" class="mb-2 block text-base font-semibold text-slate-800">{{ __('otp_code_label') }}</label>
                            <input
                                id="otp-code"
                                :value="verifyForm.code"
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                pattern="[0-9]*"
                                :maxlength="codeLength"
                                autofocus
                                class="block min-h-12 w-full rounded-xl border-slate-300 px-4 py-3 text-center font-mono text-base tracking-[0.5em] focus:border-orange-500 focus:ring-orange-500"
                                :class="{ 'border-red-400': verifyForm.errors.code }"
                                :aria-invalid="!!verifyForm.errors.code"
                                aria-describedby="otp-code-help"
                                @input="onCodeInput"
                            />
                            <p v-if="verifyForm.errors.code" class="mt-2 text-sm font-semibold text-red-700" role="alert">
                                {{ __(verifyForm.errors.code) }}
                            </p>
                            <p id="otp-code-help" class="mt-2 text-sm" :class="codeExpired ? 'font-semibold text-red-700' : 'text-slate-500'">
                                <template v-if="codeExpired">{{ __('otp_expired_hint') }}</template>
                                <template v-else>{{ __('otp_expires_in').replace(':time', formatTime(expiresIn)) }}</template>
                            </p>
                        </div>

                        <button
                            type="submit"
                            class="flex min-h-11 w-full items-center justify-center rounded-xl bg-orange-600 px-4 py-3 text-base font-bold text-white hover:bg-orange-500 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="verifyForm.processing || verifyForm.code.length !== codeLength || codeExpired"
                        >
                            {{ verifyForm.processing ? __('otp_verifying') : __('otp_verify_button') }}
                        </button>

                        <button
                            type="button"
                            class="flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-3 text-base font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="sendForm.processing || resendIn > 0"
                            @click="sendCode"
                        >
                            <span v-if="sendForm.processing">{{ __('otp_sending') }}</span>
                            <span v-else-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</span>
                            <span v-else>{{ __('otp_resend_button') }}</span>
                        </button>
                    </form>

                    <Link :href="route('dashboard')" class="flex min-h-11 w-full items-center justify-center rounded-xl px-4 py-3 text-base font-semibold text-slate-500 hover:text-slate-800">
                        {{ __('otp_skip') }}
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
