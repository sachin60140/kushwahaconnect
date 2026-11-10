<script setup>
// Inline OTP step shown under a form's mobile number field:
// "Send OTP" → enter code → "Verified ✓". The server keeps the proof in the
// session (FormOtpController) and the form's store action checks it
// (App\Rules\VerifiedMobile). Hidden while OTP is not required (SMS off).
//
// Usage: <MobileOtpField :mobile="form.mobile" :error="form.errors.mobile" />
// Sits inside the parent <form>, so it never submits it.
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    mobile: { type: String, default: '' },
    // Server validation error for the mobile field (dictionary key or plain text).
    error: { type: String, default: '' },
    // A number already on file that needs no new OTP (e.g. the saved profile number).
    unchanged: { type: String, default: null },
    idPrefix: { type: String, default: 'mobile-otp' },
});

const emit = defineEmits(['verified']);

const page = usePage();
const { __ } = useLanguage();

const config = computed(() => page.props.mobileOtp ?? { required: false, verified: [], codeLength: 6 });
const codeLength = computed(() => config.value.codeLength || 6);

// Set when this visitor's own send just put SMS into degraded mode: OTP is no
// longer required, so show the note at once instead of making them reload.
const degradedNow = ref(false);
const otpRequired = computed(() => config.value.required && !degradedNow.value);
const showDegraded = computed(() => degradedNow.value || config.value.degraded);

const normalize = (value) => {
    let digits = String(value ?? '').replace(/\D+/g, '');
    if (digits.length > 10) digits = digits.slice(-10);
    return /^[6-9]\d{9}$/.test(digits) ? digits : null;
};

const number = computed(() => normalize(props.mobile));
const verifiedHere = ref([]);

const isVerified = computed(() => {
    if (!number.value) return false;
    if (props.unchanged && normalize(props.unchanged) === number.value) return true;
    return verifiedHere.value.includes(number.value) || (config.value.verified || []).map(String).includes(number.value);
});

const sentTo = ref(null);
const code = ref('');
const sending = ref(false);
const checking = ref(false);
const message = ref('');
const resendIn = ref(0);
const expiresIn = ref(0);
let timer = null;

const startTimer = () => {
    clearInterval(timer);
    timer = setInterval(() => {
        if (resendIn.value > 0) resendIn.value -= 1;
        if (expiresIn.value > 0) expiresIn.value -= 1;
        if (resendIn.value <= 0 && expiresIn.value <= 0) clearInterval(timer);
    }, 1000);
};

onBeforeUnmount(() => clearInterval(timer));

// Editing the number after sending a code starts over for the new number.
watch(number, (value) => {
    if (sentTo.value && value !== sentTo.value) {
        sentTo.value = null;
        code.value = '';
        message.value = '';
    }
});

watch(isVerified, (value) => emit('verified', value), { immediate: true });

const codeSent = computed(() => sentTo.value !== null && sentTo.value === number.value);

const errorText = (error, fallback) => {
    const data = error?.response?.data ?? {};
    if (error?.response?.status === 429) return __('otp_error_rate_limited');
    return __(data.message || fallback);
};

const sendCode = async () => {
    if (!number.value || sending.value || resendIn.value > 0) return;
    sending.value = true;
    message.value = '';
    try {
        const { data } = await axios.post(route('mobile-otp.send'), { mobile: number.value });
        if (data.verified) {
            verifiedHere.value.push(number.value);
            return;
        }
        sentTo.value = number.value;
        code.value = '';
        resendIn.value = data.resend_in ?? 60;
        expiresIn.value = data.expires_in ?? 600;
        startTimer();
    } catch (error) {
        if (error?.response?.data?.degraded) {
            degradedNow.value = true;
            message.value = '';
            return;
        }
        message.value = errorText(error, 'otp_error_send_failed');
        const retry = error?.response?.data?.retry_after;
        if (retry) {
            resendIn.value = retry;
            startTimer();
        }
    } finally {
        sending.value = false;
    }
};

const verifyCode = async () => {
    if (checking.value || code.value.length !== codeLength.value) return;
    checking.value = true;
    message.value = '';
    try {
        await axios.post(route('mobile-otp.verify'), { mobile: number.value, code: code.value });
        verifiedHere.value.push(number.value);
        sentTo.value = null;
        clearInterval(timer);
    } catch (error) {
        message.value = errorText(error, 'otp_error_invalid');
        code.value = '';
    } finally {
        checking.value = false;
    }
};

const onCodeInput = (event) => {
    code.value = event.target.value.replace(/\D/g, '').slice(0, codeLength.value);
    event.target.value = code.value;
    message.value = '';
    if (code.value.length === codeLength.value) verifyCode();
};

const formatTime = (seconds) => `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

// The server error is replaced by the live state once the number is verified.
const serverError = computed(() => (props.error && !isVerified.value && !(degradedNow.value && props.error === 'otp_error_mobile_not_verified') ? __(props.error) : ''));
</script>

<template>
    <div class="mt-2 space-y-2">
        <template v-if="otpRequired">
            <p
                v-if="isVerified"
                class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm font-bold text-green-800"
                role="status"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                {{ __('otp_field_verified') }}
            </p>

            <div v-else class="space-y-2 rounded-lg border border-orange-200 bg-orange-50 p-3">
                <p class="text-sm text-slate-700">{{ __('otp_field_hint') }}</p>

                <button
                    v-if="!codeSent"
                    type="button"
                    class="flex min-h-11 w-full items-center justify-center rounded-lg bg-orange-600 px-4 py-2 text-base font-bold text-white hover:bg-orange-500 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
                    :disabled="!number || sending || resendIn > 0"
                    @click="sendCode"
                >
                    <span v-if="sending">{{ __('otp_sending') }}</span>
                    <span v-else-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</span>
                    <span v-else>{{ __('otp_field_send') }}</span>
                </button>
                <p v-if="!number && mobile" class="text-sm text-slate-600">{{ __('otp_error_mobile_format') }}</p>

                <div v-if="codeSent" class="space-y-2">
                    <label :for="`${idPrefix}-code`" class="block text-sm font-bold text-slate-800">{{ __('otp_code_label') }}</label>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input
                            :id="`${idPrefix}-code`"
                            :value="code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            :maxlength="codeLength"
                            :placeholder="'•'.repeat(codeLength)"
                            class="min-h-11 w-full rounded-lg border-slate-300 px-3 py-2 text-center font-mono text-base tracking-[0.4em] focus:border-orange-500 focus:ring-orange-500 sm:w-48"
                            @input="onCodeInput"
                            @keydown.enter.prevent="verifyCode"
                        />
                        <button
                            type="button"
                            class="flex min-h-11 items-center justify-center rounded-lg bg-blue-950 px-4 py-2 text-base font-bold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="checking || code.length !== codeLength"
                            @click="verifyCode"
                        >
                            {{ checking ? __('otp_verifying') : __('otp_verify_button') }}
                        </button>
                    </div>
                    <p class="text-sm text-slate-600">
                        <template v-if="expiresIn > 0">{{ __('otp_expires_in').replace(':time', formatTime(expiresIn)) }}</template>
                        <template v-else>{{ __('otp_expired_hint') }}</template>
                    </p>
                    <button
                        type="button"
                        class="min-h-11 text-sm font-bold text-orange-700 underline disabled:cursor-not-allowed disabled:text-slate-400 disabled:no-underline"
                        :disabled="sending || resendIn > 0"
                        @click="sendCode"
                    >
                        <template v-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</template>
                        <template v-else>{{ __('otp_resend_button') }}</template>
                    </button>
                </div>

                <p v-if="message" class="text-sm font-semibold text-red-700" role="alert">{{ message }}</p>
            </div>
        </template>
        <p
            v-else-if="showDegraded"
            class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-900"
            role="status"
        >
            {{ __('otp_field_degraded') }}
        </p>

        <p v-if="serverError" class="text-sm font-semibold text-red-700">{{ serverError }}</p>
    </div>
</template>
