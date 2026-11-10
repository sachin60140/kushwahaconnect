<script setup>
// SMS gateway status for admins: a red banner while SmartPing is failing
// ("degraded", OTP paused) and a small usage card. Data: SmsHealth::usage().
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    health: { type: Object, required: true },
    // Super admins can end a degraded episode from SMS Settings.
    canRetry: { type: Boolean, default: false },
});

const { __, locale } = useLanguage();
const page = usePage();
const retryForm = useForm({});

const formatTime = (iso) => {
    if (!iso) return __('sms_health_never');
    try {
        return new Intl.DateTimeFormat(locale.value === 'hi' ? 'hi-IN' : 'en-IN', {
            dateStyle: 'medium',
            timeStyle: 'short',
            timeZone: page.props.displayTimeZone || 'Asia/Kolkata',
        }).format(new Date(iso));
    } catch {
        return iso;
    }
};

const statusLabel = computed(() => __(`sms_health_status_${props.health.status}`));
const statusClass = computed(() => ({
    ok: 'bg-green-100 text-green-800',
    degraded: 'bg-red-100 text-red-800',
    off: 'bg-slate-200 text-slate-700',
}[props.health.status] || 'bg-slate-200 text-slate-700'));

const retry = () => retryForm.post(route('admin.sms-settings.retry'), { preserveScroll: true });

const tiles = computed(() => [
    { label: __('sms_health_sent_today'), value: props.health.sent_today },
    { label: __('sms_health_failed_today'), value: props.health.failed_today },
    { label: __('sms_health_sent_month'), value: props.health.sent_month },
]);
</script>

<template>
    <div class="space-y-4">
        <div
            v-if="health.degraded"
            class="rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-900"
            role="alert"
        >
            <p class="text-base font-black">{{ __('sms_health_banner_title') }}</p>
            <p class="mt-1 text-sm font-semibold">{{ __(health.degraded.reason_key) }}</p>
            <p class="mt-1 text-sm">{{ __('sms_health_banner_body') }}</p>
            <p class="mt-1 text-xs opacity-80">
                {{ __('sms_health_since') }} {{ formatTime(health.degraded.since) }} · {{ __('sms_health_until') }} {{ formatTime(health.degraded.until) }} · code {{ health.degraded.reason }}
            </p>
            <button
                v-if="canRetry"
                type="button"
                class="mt-3 flex min-h-11 w-full items-center justify-center rounded-lg bg-red-700 px-4 py-2 text-base font-bold text-white hover:bg-red-600 disabled:opacity-60 sm:w-auto"
                :disabled="retryForm.processing"
                @click="retry"
            >
                {{ __('sms_health_retry') }}
            </button>
        </div>

        <p
            v-if="health.cap_hit"
            class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"
            role="status"
        >
            {{ __('sms_health_cap_hit') }} · {{ formatTime(health.cap_hit.at) }}
        </p>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" :aria-label="__('sms_health_title')">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-lg font-black text-blue-950">{{ __('sms_health_title') }}</h3>
                <span class="rounded-full px-3 py-1 text-sm font-bold" :class="statusClass">{{ statusLabel }}</span>
            </div>
            <dl class="mt-4 grid grid-cols-3 gap-2 sm:gap-4">
                <div v-for="tile in tiles" :key="tile.label" class="rounded-lg bg-slate-50 p-3 text-center">
                    <dt class="text-xs font-semibold text-slate-600 sm:text-sm">{{ tile.label }}</dt>
                    <dd class="mt-1 text-2xl font-black text-slate-900">{{ tile.value }}</dd>
                </div>
            </dl>
            <p class="mt-3 text-sm text-slate-600">
                {{ __('sms_health_last_success') }} <span class="font-semibold text-slate-800">{{ formatTime(health.last_success_at) }}</span>
            </p>
        </section>
    </div>
</template>
