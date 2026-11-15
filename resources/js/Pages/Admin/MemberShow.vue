<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const { __, locale } = useLanguage();
const page = usePage();

const props = defineProps({
    member: Object,
    account: { type: Object, default: null },
    privacy: { type: Object, default: null },
    linked: { type: Object, default: null },
    logins: { type: Array, default: () => [] },
});

const dateFmt = (value, withTime = false) => {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    const opts = withTime
        ? { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' }
        : { day: 'numeric', month: 'short', year: 'numeric' };
    return d.toLocaleString(locale.value === 'hi' ? 'hi-IN' : 'en-IN', opts);
};

// DOB is a plain date: show it without timezone shifts.
const dob = computed(() => (props.member.dob ? dateFmt(`${String(props.member.dob).slice(0, 10)}T00:00:00`) : ''));
const yesNo = (v) => (v ? __('admin_member_yes') : __('admin_member_no'));
const label = (v) => (v ? String(v).replace(/_/g, ' ') : '');

const profileRows = computed(() => [
    ['admin_member_full_name', props.member.full_name],
    ['admin_member_father_husband', props.member.father_husband_name],
    ['admin_member_gender', props.member.gender],
    ['admin_member_dob', dob.value],
    ['admin_member_gotra', props.member.gotra],
]);
const contactRows = computed(() => [
    ['admin_member_mobile', props.member.mobile],
    ['admin_member_whatsapp', props.member.whatsapp],
    ['admin_member_email', props.member.email],
]);
const locationRows = computed(() => [
    ['admin_member_state', props.member.state_name],
    ['admin_member_district', props.member.district_name],
    ['admin_member_city', props.member.city_village],
    ['admin_member_pin', props.member.pin_code],
    ['admin_member_address', props.member.current_address],
    ['admin_member_native', props.member.native_place],
]);
const workRows = computed(() => [
    ['admin_member_education', props.member.education],
    ['admin_member_employment', label(props.member.employment_type)],
    ['admin_member_occupation', props.member.occupation],
    ['admin_member_company', props.member.company_name],
    ['admin_member_skills', props.member.skills],
]);
const communityRows = computed(() => [
    ['admin_member_social_work', props.member.social_work_interest],
    ['admin_member_volunteer_interest', yesNo(props.member.volunteer_interest)],
    ['admin_member_district_team', yesNo(props.member.join_district_team)],
]);
const adminRows = computed(() => [
    ['admin_member_profile_status', label(props.member.status)],
    ['admin_member_is_public', yesNo(props.member.is_public)],
    ['admin_member_show_mobile', yesNo(props.member.show_mobile)],
    ['admin_member_allow_whatsapp', yesNo(props.member.allow_whatsapp)],
    ['admin_member_consent', yesNo(props.member.consent)],
    ['admin_member_admin_note', props.member.admin_note],
    ['admin_member_created', dateFmt(props.member.created_at, true)],
    ['admin_member_updated', dateFmt(props.member.updated_at, true)],
]);
const socialLinks = computed(() =>
    ['facebook', 'instagram', 'linkedin', 'twitter', 'youtube']
        .filter((k) => props.member[k])
        .map((k) => ({ key: k, url: props.member[k] })),
);
const privacyRows = computed(() => props.privacy ? [
    ['admin_member_privacy_directory', yesNo(props.privacy.public_profile_visible)],
    ['admin_member_privacy_mobile', label(props.privacy.mobile_visibility)],
    ['admin_member_privacy_email', label(props.privacy.email_visibility)],
    ['admin_member_privacy_photo', label(props.privacy.photo_visibility)],
    ['admin_member_privacy_address', label(props.privacy.address_visibility)],
    ['admin_member_privacy_mat_first_name', yesNo(props.privacy.matrimonial_show_first_name_only)],
    ['admin_member_privacy_mat_photo', label(props.privacy.matrimonial_photo_visibility)],
    ['admin_member_privacy_mat_income', yesNo(props.privacy.matrimonial_hide_income)],
] : []);

const connectionsSummary = computed(() => {
    const c = props.linked?.connections || { total: 0, accepted: 0, pending: 0 };
    return __('admin_member_connections_summary')
        .replace(':total', c.total).replace(':accepted', c.accepted).replace(':pending', c.pending);
});

const statusClass = (s) => ({
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
    blocked: 'bg-red-100 text-red-800',
    suspended: 'bg-red-100 text-red-800',
}[String(s || '').toLowerCase()] || 'bg-yellow-100 text-yellow-800');

const card = 'rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-6';
const h3 = 'mb-3 text-base font-bold text-gray-900';
const linkBtn = 'inline-flex min-h-11 items-center rounded-lg px-3 font-bold text-indigo-700 underline hover:bg-indigo-50';
</script>

<template>
    <Head :title="__('admin_member_details_title')" />

    <AuthenticatedLayout :show-flash="false">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="min-w-0 break-words text-xl font-semibold leading-tight text-gray-800">
                    {{ __('admin_member_details_title') }}
                </h2>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('admin.members')" class="inline-flex min-h-11 items-center rounded-lg bg-gray-100 px-4 text-base font-bold text-gray-700 hover:bg-gray-200">{{ __('admin_member_back') }}</Link>
                    <Link :href="route('admin.members.edit', member.id)" class="inline-flex min-h-11 items-center rounded-lg bg-orange-600 px-4 text-base font-bold text-white hover:bg-orange-700">{{ __('admin_member_edit') }}</Link>
                </div>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div v-if="page.props.flash?.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-base font-bold text-green-800">
                    {{ __(page.props.flash.success) }}
                </div>

                <!-- Summary -->
                <section :class="card" class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <img v-if="member.profile_photo_url" :src="member.profile_photo_url" :alt="member.full_name" class="h-24 w-24 shrink-0 rounded-full object-cover" />
                    <div v-else class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm text-gray-500">{{ __('admin_member_no_photo') }}</div>
                    <div class="min-w-0">
                        <p class="break-words text-2xl font-extrabold text-gray-900">{{ member.full_name }}</p>
                        <p class="mt-1 text-base text-gray-600">{{ member.mobile }}<span v-if="member.email" class="break-all"> · {{ member.email }}</span></p>
                        <div class="mt-2 flex flex-wrap gap-2 text-sm font-bold">
                            <span class="rounded-sm px-2 py-0.5 uppercase" :class="statusClass(member.status)">{{ label(member.status) }}</span>
                            <span v-if="account" class="rounded-sm px-2 py-0.5" :class="account.mobile_verified_at ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                {{ account.mobile_verified_at ? __('admin_mobile_verified') : __('admin_mobile_not_verified') }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-500">
                            {{ __('admin_member_updated') }}: {{ dateFmt(member.updated_at, true) }} — {{ __('admin_member_updated_hint') }}
                        </p>
                    </div>
                </section>

                <!-- Profile -->
                <section :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_profile') }}</h3>
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <dl v-for="(rows, i) in [profileRows, contactRows, locationRows, workRows, communityRows]" :key="i" class="space-y-2 text-base">
                            <div v-for="[key, value] in rows" :key="key" class="grid grid-cols-1 gap-0.5 sm:grid-cols-[minmax(0,10rem)_1fr] sm:gap-3">
                                <dt class="text-sm text-gray-500">{{ __(key) }}</dt>
                                <dd class="min-w-0 break-words font-semibold text-gray-900">
                                    <template v-if="value !== '' && value !== null && value !== undefined">{{ value }}</template>
                                    <span v-else class="font-normal italic text-gray-400">{{ __('admin_member_none') }}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div v-if="socialLinks.length" class="mt-4">
                        <h4 class="text-sm font-bold text-gray-700">{{ __('admin_member_section_social') }}</h4>
                        <ul class="mt-1 flex flex-wrap gap-2">
                            <li v-for="s in socialLinks" :key="s.key">
                                <a :href="s.url" target="_blank" rel="noopener noreferrer nofollow" :class="linkBtn" class="capitalize">{{ s.key }}</a>
                            </li>
                        </ul>
                    </div>
                </section>

                <!-- Admin settings -->
                <section :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_admin') }}</h3>
                    <dl class="grid grid-cols-1 gap-2 text-base md:grid-cols-2">
                        <div v-for="[key, value] in adminRows" :key="key" class="min-w-0">
                            <dt class="text-sm text-gray-500">{{ __(key) }}</dt>
                            <dd class="break-words font-semibold text-gray-900">
                                <template v-if="value">{{ value }}</template>
                                <span v-else class="font-normal italic text-gray-400">{{ __('admin_member_none') }}</span>
                            </dd>
                        </div>
                    </dl>
                </section>

                <!-- Account -->
                <section :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_account') }}</h3>
                    <p v-if="!account" class="text-base text-gray-600">{{ __('admin_member_no_account') }}</p>
                    <dl v-else class="grid grid-cols-1 gap-3 text-base md:grid-cols-2">
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_account_name') }}</dt><dd class="break-words font-semibold text-gray-900">{{ account.name }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_email') }}</dt><dd class="break-all font-semibold text-gray-900">{{ account.email || __('admin_member_none') }}</dd></div>
                        <div>
                            <dt class="text-sm text-gray-500">{{ __('admin_member_mobile') }}</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ account.mobile || __('admin_member_none') }}
                                <span class="ml-1 rounded-sm px-2 py-0.5 text-xs font-bold" :class="account.mobile_verified_at ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'">
                                    {{ account.mobile_verified_at ? __('admin_mobile_verified') : __('admin_mobile_not_verified') }}
                                </span>
                            </dd>
                        </div>
                        <div v-if="account.mobile_verified_at"><dt class="text-sm text-gray-500">{{ __('admin_member_verified_at') }}</dt><dd class="font-semibold text-gray-900">{{ dateFmt(account.mobile_verified_at, true) }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_role') }}</dt><dd class="font-semibold text-gray-900">{{ label(account.role) || 'user' }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_account_status') }}</dt><dd><span class="rounded-sm px-2 py-0.5 text-sm font-bold" :class="statusClass(account.status)">{{ label(account.status) }}</span></dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_created') }}</dt><dd class="font-semibold text-gray-900">{{ dateFmt(account.created_at, true) }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_last_login') }}</dt><dd class="font-semibold text-gray-900">{{ account.last_login_at ? dateFmt(account.last_login_at, true) : __('admin_member_never') }}</dd></div>
                        <div><dt class="text-sm text-gray-500">{{ __('admin_member_language') }}</dt><dd class="font-semibold uppercase text-gray-900">{{ account.language_preference }}</dd></div>
                        <div v-if="account.email_verified_at"><dt class="text-sm text-gray-500">{{ __('admin_member_email_verified') }}</dt><dd class="font-semibold text-gray-900">{{ dateFmt(account.email_verified_at, true) }}</dd></div>
                    </dl>
                </section>

                <!-- Privacy -->
                <section v-if="privacy" :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_privacy') }}</h3>
                    <p v-if="!privacy.customised" class="mb-3 text-sm text-gray-500">{{ __('admin_member_privacy_defaults') }}</p>
                    <dl class="grid grid-cols-1 gap-2 text-base md:grid-cols-2">
                        <div v-for="[key, value] in privacyRows" :key="key">
                            <dt class="text-sm text-gray-500">{{ __(key) }}</dt>
                            <dd class="font-semibold text-gray-900">{{ value }}</dd>
                        </div>
                    </dl>
                </section>

                <!-- Linked records -->
                <section v-if="linked" :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_linked') }}</h3>
                    <div class="space-y-5 text-base">
                        <div>
                            <h4 class="font-bold text-gray-800">{{ __('admin_member_matrimonial') }} ({{ linked.matrimonial.length }})</h4>
                            <p v-if="!linked.matrimonial.length" class="text-gray-500">{{ __('admin_member_none_linked') }}</p>
                            <ul v-else class="mt-1 space-y-2">
                                <li v-for="m in linked.matrimonial" :key="m.id" class="rounded-lg bg-gray-50 p-3">
                                    <p class="break-words font-semibold text-gray-900">{{ m.full_name }} · {{ m.gender }} · {{ label(m.marital_status) }}<span v-if="m.current_city"> · {{ m.current_city }}</span></p>
                                    <p class="text-sm text-gray-600"><span class="rounded-sm px-2 py-0.5 font-bold uppercase" :class="statusClass(m.status)">{{ m.status }}</span> {{ dateFmt(m.created_at) }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <a :href="m.admin_url" :class="linkBtn">{{ __('admin_member_open_admin') }}</a>
                                        <a v-if="m.id_proof_url" :href="m.id_proof_url" target="_blank" rel="noopener" :class="linkBtn">{{ __('admin_member_id_proof') }}</a>
                                        <a v-if="m.biodata_url" :href="m.biodata_url" target="_blank" rel="noopener" :class="linkBtn">{{ __('admin_member_biodata') }}</a>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800">{{ __('admin_member_businesses') }} ({{ linked.businesses.length }})</h4>
                            <p v-if="!linked.businesses.length" class="text-gray-500">{{ __('admin_member_none_linked') }}</p>
                            <ul v-else class="mt-1 space-y-2">
                                <li v-for="b in linked.businesses" :key="b.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 p-3">
                                    <span class="min-w-0 break-words"><span class="font-semibold text-gray-900">{{ b.business_name }}</span> · {{ b.category }}<span v-if="b.city"> · {{ b.city }}</span> <span class="rounded-sm px-2 py-0.5 text-sm font-bold uppercase" :class="statusClass(b.status)">{{ b.status }}</span></span>
                                    <Link :href="b.admin_url" :class="linkBtn">{{ __('admin_member_open_admin') }}</Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800">{{ __('admin_member_help_requests') }} ({{ linked.help_requests.length }})</h4>
                            <p v-if="!linked.help_requests.length" class="text-gray-500">{{ __('admin_member_none_linked') }}</p>
                            <ul v-else class="mt-1 space-y-2">
                                <li v-for="h in linked.help_requests" :key="h.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 p-3">
                                    <span class="min-w-0 break-words"><span class="font-semibold text-gray-900">{{ h.title }}</span> · {{ h.category }} · {{ h.urgency }} <span class="rounded-sm px-2 py-0.5 text-sm font-bold" :class="statusClass(h.status)">{{ h.status }}</span> <span class="text-sm text-gray-500">{{ dateFmt(h.created_at) }}</span></span>
                                    <Link :href="h.admin_url" :class="linkBtn">{{ __('admin_member_open_admin') }}</Link>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h4 class="font-bold text-gray-800">{{ __('admin_member_volunteer') }}</h4>
                            <p v-if="!linked.volunteer" class="text-gray-500">{{ __('admin_member_none_linked') }}</p>
                            <div v-else class="mt-1 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-gray-50 p-3">
                                <span class="min-w-0 break-words"><span class="rounded-sm px-2 py-0.5 text-sm font-bold" :class="statusClass(linked.volunteer.status)">{{ linked.volunteer.status }}</span> {{ linked.volunteer.city }}<span v-if="linked.volunteer.skills"> · {{ linked.volunteer.skills }}</span></span>
                                <Link :href="linked.volunteer.admin_url" :class="linkBtn">{{ __('admin_member_open_admin') }}</Link>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-lg bg-gray-50 p-3">
                                <h4 class="font-bold text-gray-800">{{ __('admin_member_connections') }}</h4>
                                <p class="text-gray-700">{{ connectionsSummary }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-3">
                                <h4 class="font-bold text-gray-800">{{ __('admin_member_shortlists') }}</h4>
                                <p class="text-gray-700">{{ linked.shortlists }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Login history -->
                <section v-if="account" :class="card">
                    <h3 :class="h3">{{ __('admin_member_section_logins') }}</h3>
                    <p v-if="!logins.length" class="text-base text-gray-500">{{ __('admin_member_no_logins') }}</p>
                    <ul v-else class="divide-y divide-gray-100 text-base">
                        <li v-for="l in logins" :key="l.id" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2">
                            <span class="font-semibold text-gray-900">{{ dateFmt(l.login_at, true) }}</span>
                            <span class="min-w-0 break-all text-sm text-gray-600">{{ __('admin_member_ip') }} {{ l.ip_address || '—' }}<span v-if="l.browser || l.platform"> · {{ [l.browser, l.platform].filter(Boolean).join(' / ') }}</span></span>
                            <span class="rounded-sm px-2 py-0.5 text-sm font-bold" :class="l.status === 'failed' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
                                {{ l.status === 'failed' ? __('admin_member_login_failed') : __('admin_member_login_success') }}
                            </span>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
