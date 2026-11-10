<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    settings: { type: Object, required: true },
    defaults: { type: Object, required: true },
    hasSavedPassword: { type: Boolean, default: false },
    hasEnvUsername: { type: Boolean, default: false },
    hasEnvPassword: { type: Boolean, default: false },
    configured: { type: Boolean, default: false },
    // { ok, http, state, status_code, description } from the last "Send test SMS".
    testResult: { type: Object, default: null },
});

const { __ } = useLanguage();

const form = useForm({
    enabled: props.settings.enabled,
    username: props.settings.username,
    password: '',
    sender_id: props.settings.sender_id,
    dlt_content_id: props.settings.dlt_content_id,
    dlt_principal_entity_id: props.settings.dlt_principal_entity_id,
    base_url: props.settings.base_url,
    otp_message: props.settings.otp_message,
});

const testForm = useForm({ mobile: '' });

const save = () => {
    form.post(route('admin.sms-settings.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('password'),
    });
};

const sendTest = () => {
    testForm.post(route('admin.sms-settings.test'), { preserveScroll: true });
};

const inputClass = 'mt-1 block min-h-11 w-full rounded-lg border-slate-300 px-3 py-2 text-base focus:border-orange-500 focus:ring-orange-500';
const labelClass = 'block text-sm font-bold text-slate-800';
const hintClass = 'mt-1 text-sm text-slate-500';
const errorClass = 'mt-1 text-sm font-semibold text-red-700';
</script>

<template>
    <Head :title="__('sms_settings_title')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ __('sms_settings_title') }}</h2>
        </template>

        <div class="px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl space-y-6">
                <p
                    class="rounded-lg border px-4 py-3 text-sm font-semibold"
                    :class="configured ? 'border-green-200 bg-green-50 text-green-800' : 'border-amber-200 bg-amber-50 text-amber-900'"
                >
                    {{ configured ? __('sms_settings_status_live') : __('sms_settings_status_off') }}
                    <span class="mt-1 block font-normal">
                        {{ configured ? __('sms_settings_otp_enforced') : __('sms_settings_otp_not_enforced') }}
                    </span>
                </p>

                <form class="space-y-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" @submit.prevent="save">
                    <label class="flex min-h-11 cursor-pointer items-center gap-3">
                        <input v-model="form.enabled" type="checkbox" class="h-6 w-6 rounded border-slate-300 text-orange-600 focus:ring-orange-500" />
                        <span class="text-base font-bold text-slate-900">{{ __('sms_settings_enabled') }}</span>
                    </label>
                    <p :class="hintClass" class="-mt-3">{{ __('sms_settings_enabled_hint') }}</p>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="sms-username" :class="labelClass">{{ __('sms_settings_username') }}</label>
                            <input id="sms-username" v-model="form.username" type="text" autocomplete="off" :class="inputClass" />
                            <p v-if="!form.username && hasEnvUsername" :class="hintClass">{{ __('sms_settings_using_env') }}</p>
                            <p v-if="form.errors.username" :class="errorClass">{{ __(form.errors.username) }}</p>
                        </div>
                        <div>
                            <label for="sms-password" :class="labelClass">{{ __('sms_settings_password') }}</label>
                            <input
                                id="sms-password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                :placeholder="hasSavedPassword ? '••••••••' : ''"
                                :class="inputClass"
                            />
                            <p :class="hintClass">
                                <template v-if="hasSavedPassword">{{ __('sms_settings_password_saved') }}</template>
                                <template v-else-if="hasEnvPassword">{{ __('sms_settings_using_env') }}</template>
                                <template v-else>{{ __('sms_settings_password_none') }}</template>
                            </p>
                            <p v-if="form.errors.password" :class="errorClass">{{ __(form.errors.password) }}</p>
                        </div>
                        <div>
                            <label for="sms-sender" :class="labelClass">{{ __('sms_settings_sender_id') }}</label>
                            <input id="sms-sender" v-model="form.sender_id" type="text" :placeholder="defaults.sender_id" :class="inputClass" />
                            <p v-if="form.errors.sender_id" :class="errorClass">{{ __(form.errors.sender_id) }}</p>
                        </div>
                        <div>
                            <label for="sms-content" :class="labelClass">{{ __('sms_settings_dlt_content_id') }}</label>
                            <input id="sms-content" v-model="form.dlt_content_id" type="text" inputmode="numeric" :placeholder="defaults.dlt_content_id" :class="inputClass" />
                            <p v-if="form.errors.dlt_content_id" :class="errorClass">{{ __(form.errors.dlt_content_id) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sms-entity" :class="labelClass">{{ __('sms_settings_dlt_entity_id') }}</label>
                            <input id="sms-entity" v-model="form.dlt_principal_entity_id" type="text" inputmode="numeric" :placeholder="defaults.dlt_principal_entity_id" :class="inputClass" />
                            <p v-if="form.errors.dlt_principal_entity_id" :class="errorClass">{{ __(form.errors.dlt_principal_entity_id) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sms-url" :class="labelClass">{{ __('sms_settings_base_url') }}</label>
                            <input id="sms-url" v-model="form.base_url" type="url" :placeholder="defaults.base_url" :class="inputClass" />
                            <p v-if="form.errors.base_url" :class="errorClass">{{ __(form.errors.base_url) }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="sms-template" :class="labelClass">{{ __('sms_settings_template') }}</label>
                            <textarea id="sms-template" v-model="form.otp_message" rows="3" :placeholder="defaults.otp_message" :class="inputClass"></textarea>
                            <p :class="hintClass">{{ __('sms_settings_template_hint') }}</p>
                            <p v-if="form.errors.otp_message" :class="errorClass">{{ __(form.errors.otp_message) }}</p>
                        </div>
                    </div>
                    <p :class="hintClass">{{ __('sms_settings_blank_hint') }}</p>

                    <button
                        type="submit"
                        class="flex min-h-11 w-full items-center justify-center rounded-xl bg-orange-600 px-5 py-3 text-base font-bold text-white hover:bg-orange-500 disabled:opacity-60 sm:w-auto"
                        :disabled="form.processing"
                    >
                        {{ __('sms_settings_save') }}
                    </button>
                </form>

                <form class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" @submit.prevent="sendTest">
                    <h3 class="text-lg font-black text-blue-950">{{ __('sms_settings_test_title') }}</h3>
                    <p :class="hintClass" class="mt-0">{{ __('sms_settings_test_hint') }}</p>
                    <div>
                        <label for="sms-test-mobile" :class="labelClass">{{ __('sms_settings_test_mobile') }}</label>
                        <input id="sms-test-mobile" v-model="testForm.mobile" type="tel" inputmode="numeric" autocomplete="tel" :class="inputClass" />
                        <p v-if="testForm.errors.mobile" :class="errorClass">{{ __(testForm.errors.mobile) }}</p>
                    </div>
                    <button
                        type="submit"
                        class="flex min-h-11 w-full items-center justify-center rounded-xl bg-blue-950 px-5 py-3 text-base font-bold text-white hover:bg-blue-900 disabled:opacity-60 sm:w-auto"
                        :disabled="testForm.processing || !testForm.mobile"
                    >
                        {{ testForm.processing ? __('otp_sending') : __('sms_settings_test_send') }}
                    </button>

                    <div
                        v-if="testResult"
                        class="rounded-lg border px-4 py-3 text-sm"
                        :class="testResult.ok ? 'border-green-200 bg-green-50 text-green-900' : 'border-red-200 bg-red-50 text-red-900'"
                        role="status"
                    >
                        <p class="font-bold">{{ testResult.ok ? __('sms_settings_test_ok') : __('sms_settings_test_failed') }}</p>
                        <p v-if="testResult.hint" class="mt-1 font-semibold">{{ __(testResult.hint) }}</p>
                        <p v-if="testResult.description" class="mt-1 break-words">{{ testResult.description }}</p>
                        <p class="mt-1 break-words font-mono text-xs opacity-80">
                            HTTP {{ testResult.http ?? '—' }} · state {{ testResult.state ?? '—' }} · statusCode {{ testResult.status_code ?? '—' }}
                            <template v-if="testResult.transaction_id"> · transactionId {{ testResult.transaction_id }}</template>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
