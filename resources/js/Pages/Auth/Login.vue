<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    // OtpLoginController::pageState(): { available, mobile, resend_in, expires_in, codeLength }
    otpLogin: {
        type: Object,
        default: () => ({ available: false, mobile: null, resend_in: 0, expires_in: 0, codeLength: 6 }),
    },
});

const { __ } = useLanguage();

// Mobile OTP is the default whenever SMS is available.
const mode = ref(props.otpLogin.available ? 'otp' : 'password');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

// ---- Mobile OTP sign-in ----
const codeLength = computed(() => props.otpLogin.codeLength || 6);
const sendForm = useForm({ mobile: props.otpLogin.mobile || '' });
const verifyForm = useForm({ mobile: props.otpLogin.mobile || '', otp: '', remember: false });
const resetForm = useForm({});

const codeSent = computed(() => !!props.otpLogin.mobile);
const maskedMobile = computed(() => (props.otpLogin.mobile ? `••••••${String(props.otpLogin.mobile).slice(-4)}` : ''));

const resendIn = ref(0);
const expiresIn = ref(0);
let timer = null;

const syncTimers = () => {
    resendIn.value = props.otpLogin.resend_in || 0;
    expiresIn.value = props.otpLogin.expires_in || 0;
    if (props.otpLogin.mobile) {
        verifyForm.mobile = props.otpLogin.mobile;
        sendForm.mobile = props.otpLogin.mobile;
    }
};
watch(() => props.otpLogin, syncTimers, { immediate: true, deep: true });

onMounted(() => {
    timer = setInterval(() => {
        if (resendIn.value > 0) resendIn.value -= 1;
        if (expiresIn.value > 0) expiresIn.value -= 1;
    }, 1000);
});
onBeforeUnmount(() => clearInterval(timer));

const formatTime = (seconds) => `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;

const onMobileInput = (event) => {
    sendForm.mobile = event.target.value.replace(/[^\d+ ]/g, '').slice(0, 14);
    event.target.value = sendForm.mobile;
    sendForm.clearErrors('mobile');
};

const onCodeInput = (event) => {
    verifyForm.otp = event.target.value.replace(/\D/g, '').slice(0, codeLength.value);
    event.target.value = verifyForm.otp;
    verifyForm.clearErrors('otp');
};

const sendCode = () => {
    sendForm.post(route('login.otp.generate'), {
        preserveScroll: true,
        onSuccess: () => {
            verifyForm.reset('otp');
            verifyForm.clearErrors();
        },
    });
};

const verifyCode = () => {
    verifyForm.post(route('login.otp.verify'), {
        preserveScroll: true,
        onError: () => verifyForm.reset('otp'),
    });
};

const changeNumber = () => {
    resetForm.post(route('login.otp.reset'), {
        preserveScroll: true,
        onSuccess: () => {
            verifyForm.reset('otp');
            verifyForm.clearErrors();
            sendForm.clearErrors();
        },
    });
};

const mobileError = computed(() => sendForm.errors.mobile || verifyForm.errors.mobile || '');
const statusText = computed(() => (props.status ? __(props.status) : ''));

const tabClass = (active) => [
    'flex min-h-11 flex-1 items-center justify-center rounded-lg px-3 py-2 text-sm font-bold transition-colors',
    active ? 'bg-white text-blue-950 shadow-sm' : 'text-gray-600 hover:text-blue-950',
];
const inputClass = 'mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-xs px-4 py-3 text-base';
const primaryButton = 'w-full flex min-h-11 justify-center py-3.5 px-4 border border-transparent rounded-xl shadow-md text-base font-bold text-white bg-linear-to-r from-orange-600 to-orange-500 hover:from-orange-500 hover:to-orange-400 focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed';
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="statusText" class="mb-4 text-sm font-medium text-green-600" role="status">
            {{ statusText }}
        </div>

        <div class="text-center mb-6">
            <h2 class="text-3xl font-black text-blue-950 tracking-tight">Welcome Back</h2>
            <p class="text-base text-gray-500 mt-2">Please sign in to your community account</p>
        </div>

        <!-- Choose how to sign in -->
        <div v-if="otpLogin.available" class="mb-6 flex gap-1 rounded-xl bg-gray-100 p-1" role="tablist">
            <button type="button" role="tab" :aria-selected="mode === 'otp'" :class="tabClass(mode === 'otp')" @click="mode = 'otp'">
                {{ __('otp_login_tab_otp') }}
            </button>
            <button type="button" role="tab" :aria-selected="mode === 'password'" :class="tabClass(mode === 'password')" @click="mode = 'password'">
                {{ __('otp_login_tab_password') }}
            </button>
        </div>
        <p v-else class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900" role="status">
            {{ __('otp_login_unavailable') }}
        </p>

        <!-- Mobile OTP -->
        <div v-if="mode === 'otp' && otpLogin.available" class="space-y-5">
            <form v-if="!codeSent" class="space-y-5" @submit.prevent="sendCode">
                <div>
                    <InputLabel for="otp-mobile" :value="__('otp_login_mobile_label')" class="text-gray-700 font-semibold" />
                    <input
                        id="otp-mobile"
                        :value="sendForm.mobile"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        required
                        autofocus
                        :placeholder="__('otp_login_mobile_placeholder')"
                        :class="inputClass"
                        @input="onMobileInput"
                    />
                    <p v-if="mobileError" class="mt-2 text-sm font-semibold text-red-600" role="alert">
                        {{ __(mobileError) }}
                        <Link v-if="mobileError === 'otp_login_no_account'" :href="route('join')" class="mt-1 block font-bold text-green-700 underline">
                            {{ __('otp_login_join_link') }}
                        </Link>
                    </p>
                </div>
                <button type="submit" :class="primaryButton" :disabled="sendForm.processing || !sendForm.mobile || resendIn > 0">
                    <span v-if="sendForm.processing">{{ __('otp_sending') }}</span>
                    <span v-else-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</span>
                    <span v-else>{{ __('otp_field_send') }}</span>
                </button>
            </form>

            <form v-else class="space-y-5" @submit.prevent="verifyCode">
                <p class="text-base text-gray-600">
                    {{ __('otp_login_code_sent_to') }}
                    <span class="font-mono font-bold tracking-wider text-gray-900">{{ maskedMobile }}</span>
                </p>
                <p v-if="mobileError" class="text-sm font-semibold text-red-600" role="alert">{{ __(mobileError) }}</p>

                <div>
                    <label for="otp-code" class="block text-sm font-semibold text-gray-700">{{ __('otp_code_label') }}</label>
                    <input
                        id="otp-code"
                        :value="verifyForm.otp"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        :maxlength="codeLength"
                        :placeholder="'•'.repeat(codeLength)"
                        required
                        autofocus
                        :class="[inputClass, 'text-center font-mono text-xl tracking-[0.5em]']"
                        @input="onCodeInput"
                    />
                    <p v-if="verifyForm.errors.otp" class="mt-2 text-sm font-semibold text-red-600" role="alert">{{ __(verifyForm.errors.otp) }}</p>
                    <p class="mt-2 text-sm text-gray-600">
                        <template v-if="expiresIn > 0">{{ __('otp_expires_in').replace(':time', formatTime(expiresIn)) }}</template>
                        <template v-else>{{ __('otp_expired_hint') }}</template>
                    </p>
                </div>

                <label class="flex min-h-11 items-center">
                    <Checkbox name="remember" v-model:checked="verifyForm.remember" class="text-orange-600 focus:ring-orange-500 rounded-sm border-gray-300" />
                    <span class="ms-2 text-sm text-gray-600 font-medium">Remember me</span>
                </label>

                <button type="submit" :class="primaryButton" :disabled="verifyForm.processing || verifyForm.otp.length !== codeLength">
                    <span v-if="verifyForm.processing">{{ __('otp_login_signing_in') }}</span>
                    <span v-else>{{ __('otp_login_submit') }}</span>
                </button>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <button
                        type="button"
                        class="min-h-11 text-sm font-bold text-orange-600 hover:underline disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline"
                        :disabled="sendForm.processing || resendIn > 0"
                        @click="sendCode"
                    >
                        <template v-if="resendIn > 0">{{ __('otp_resend_wait').replace(':seconds', resendIn) }}</template>
                        <template v-else>{{ __('otp_resend_button') }}</template>
                    </button>
                    <button type="button" class="min-h-11 text-sm font-semibold text-gray-600 hover:underline" :disabled="resetForm.processing" @click="changeNumber">
                        {{ __('otp_login_change_number') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Password -->
        <form v-else @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email or Mobile Number" class="text-gray-700 font-semibold" />

                <TextInput
                    id="email"
                    type="text"
                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-xs px-4 py-3"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email or mobile number"
                />

                <InputError class="mt-2" :message="form.errors.email ? __(form.errors.email) : ''" />
            </div>

            <div>
                <InputLabel for="password" value="Password" class="text-gray-700 font-semibold" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-lg shadow-xs px-4 py-3"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between mt-2">
                <label class="flex min-h-11 items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-orange-600 focus:ring-orange-500 rounded-sm border-gray-300" />
                    <span class="ms-2 text-sm text-gray-600 font-medium">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="flex min-h-11 items-center text-sm font-semibold text-orange-600 hover:text-orange-500 hover:underline transition-colors"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="pt-4">
                <button :class="primaryButton" :disabled="form.processing">
                    <span v-if="form.processing">Signing in...</span>
                    <span v-else>Sign in securely</span>
                </button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center relative">
            <p class="text-sm text-gray-600">
                Don't have an account yet?
                <Link :href="route('join')" class="font-bold text-green-700 hover:text-green-600 hover:underline transition-colors ml-1">Join Community</Link>
            </p>
        </div>
    </GuestLayout>
</template>
