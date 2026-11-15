<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const { __ } = useLanguage();

const props = defineProps({
    member: Object,
    account: { type: Object, default: null },
    states: Array,
    districts: Array,
    accountStatuses: Array,
    profileStatuses: Array,
    isSelf: { type: Boolean, default: false },
});

const m = props.member;
const form = useForm({
    full_name: m.full_name || '',
    father_husband_name: m.father_husband_name || '',
    // Older saves used lowercase; the editor options are capitalised.
    gender: { male: 'Male', female: 'Female', other: 'Other' }[String(m.gender || 'male').toLowerCase()] || m.gender,
    dob: m.dob ? String(m.dob).slice(0, 10) : '',
    mobile: m.mobile || '',
    whatsapp: m.whatsapp || '',
    // One email for profile + login account (kept in sync on save).
    email: m.email || props.account?.email || '',
    state_id: m.state_id || '',
    district_id: m.district_id || '',
    city_village: m.city_village || '',
    pin_code: m.pin_code || '',
    current_address: m.current_address || '',
    native_place: m.native_place || '',
    education: m.education || '',
    occupation: m.occupation || '',
    employment_type: m.employment_type || '',
    company_name: m.company_name || '',
    skills: m.skills || '',
    gotra: m.gotra || '',
    social_work_interest: m.social_work_interest || '',
    volunteer_interest: Boolean(m.volunteer_interest),
    join_district_team: Boolean(m.join_district_team),
    facebook: m.facebook || '',
    instagram: m.instagram || '',
    linkedin: m.linkedin || '',
    twitter: m.twitter || '',
    youtube: m.youtube || '',
    status: m.status || 'approved',
    admin_note: m.admin_note || '',
    is_public: Boolean(m.is_public),
    show_mobile: Boolean(m.show_mobile),
    allow_whatsapp: Boolean(m.allow_whatsapp),
    account_status: props.account?.status || '',
    profile_photo: null,
    remove_photo: false,
});

const availableDistricts = computed(() => props.districts.filter((d) => d.state_id === Number(form.state_id)));

watch(() => form.state_id, () => {
    if (!availableDistricts.value.find((d) => d.id === Number(form.district_id))) {
        form.district_id = '';
    }
});

const mobileChanged = computed(() => form.mobile.replace(/\D/g, '').slice(-10) !== String(m.mobile || '').replace(/\D/g, '').slice(-10));

const onPhoto = (e) => {
    form.profile_photo = e.target.files[0] || null;
    if (form.profile_photo) form.remove_photo = false;
};

const submit = () => {
    // Multipart for the photo; PUT is spoofed because browsers only send files with POST.
    form.transform((data) => {
        const out = { ...data, _method: 'put' };
        if (!props.account) delete out.account_status;
        return out;
    }).post(route('admin.members.update', m.id), { forceFormData: true, preserveScroll: true });
};

const err = (field) => (form.errors[field] ? __(form.errors[field]) : '');

const input = 'mt-1 block min-h-11 w-full rounded-lg border-gray-300 text-base shadow-xs focus:border-orange-500 focus:ring-orange-500';
const lbl = 'block text-sm font-semibold text-gray-700';
const box = 'rounded-xl border border-gray-200 bg-white p-4 shadow-xs sm:p-6';
const check = 'flex min-h-11 items-center gap-3 text-base text-gray-800';
const textFields = [
    ['full_name', 'admin_member_full_name', 'text'],
    ['father_husband_name', 'admin_member_father_husband', 'text'],
];
</script>

<template>
    <Head :title="__('admin_member_edit_title')" />

    <AuthenticatedLayout :show-flash="false">
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="min-w-0 break-words text-xl font-semibold leading-tight text-gray-800">
                    {{ __('admin_member_edit_title') }}: {{ member.full_name }}
                </h2>
                <Link :href="route('admin.members.show', member.id)" class="inline-flex min-h-11 items-center rounded-lg bg-gray-100 px-4 text-base font-bold text-gray-700 hover:bg-gray-200">{{ __('admin_member_cancel') }}</Link>
            </div>
        </template>

        <div class="py-6 sm:py-10">
            <form class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8" novalidate @submit.prevent="submit">
                <p class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">{{ __('admin_member_sync_hint') }}</p>

                <!-- Photo + personal -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_profile') }}</h3>
                    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                        <img v-if="member.profile_photo_url && !form.remove_photo" :src="member.profile_photo_url" alt="" class="h-20 w-20 shrink-0 rounded-full object-cover" />
                        <div v-else class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs text-gray-500">{{ __('admin_member_no_photo') }}</div>
                        <div class="min-w-0 flex-1">
                            <label for="profile_photo" :class="lbl">{{ __('admin_member_photo_replace') }}</label>
                            <input id="profile_photo" type="file" accept="image/jpeg,image/png,image/jpg" class="mt-1 block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2 text-base" @input="onPhoto" />
                            <p v-if="err('profile_photo')" class="mt-1 text-sm text-red-700">{{ err('profile_photo') }}</p>
                            <label v-if="member.profile_photo_url && !form.profile_photo" :class="check">
                                <input v-model="form.remove_photo" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />
                                {{ __('admin_member_photo_remove') }}
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="[field, key, type] in textFields" :key="field">
                            <label :for="field" :class="lbl">{{ __(key) }}<span v-if="field === 'full_name'"> *</span></label>
                            <input :id="field" v-model="form[field]" :type="type" :class="input" />
                            <p v-if="err(field)" class="mt-1 text-sm text-red-700">{{ err(field) }}</p>
                        </div>
                        <div>
                            <label for="gender" :class="lbl">{{ __('admin_member_gender') }} *</label>
                            <select id="gender" v-model="form.gender" :class="input">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                            <p v-if="err('gender')" class="mt-1 text-sm text-red-700">{{ err('gender') }}</p>
                        </div>
                        <div>
                            <label for="dob" :class="lbl">{{ __('admin_member_dob') }}</label>
                            <input id="dob" v-model="form.dob" type="date" :class="input" />
                            <p v-if="err('dob')" class="mt-1 text-sm text-red-700">{{ err('dob') }}</p>
                        </div>
                        <div>
                            <label for="gotra" :class="lbl">{{ __('admin_member_gotra') }}</label>
                            <input id="gotra" v-model="form.gotra" type="text" :class="input" />
                            <p v-if="err('gotra')" class="mt-1 text-sm text-red-700">{{ err('gotra') }}</p>
                        </div>
                    </div>
                </section>

                <!-- Contact -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_contact') }}</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="mobile" :class="lbl">{{ __('admin_member_mobile') }} *</label>
                            <input id="mobile" v-model="form.mobile" type="tel" inputmode="numeric" autocomplete="off" :class="input" />
                            <p v-if="err('mobile')" class="mt-1 text-sm text-red-700">{{ err('mobile') }}</p>
                            <p v-if="mobileChanged && account" class="mt-1 text-sm text-orange-800">{{ __('admin_mobile_edit_hint') }}</p>
                        </div>
                        <div>
                            <label for="whatsapp" :class="lbl">{{ __('admin_member_whatsapp') }}</label>
                            <input id="whatsapp" v-model="form.whatsapp" type="tel" inputmode="numeric" :class="input" />
                            <p v-if="err('whatsapp')" class="mt-1 text-sm text-red-700">{{ err('whatsapp') }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="email" :class="lbl">{{ __('admin_member_email') }}</label>
                            <input id="email" v-model="form.email" type="email" autocomplete="off" :class="input" />
                            <p v-if="err('email')" class="mt-1 text-sm text-red-700">{{ err('email') }}</p>
                        </div>
                    </div>
                </section>

                <!-- Location -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_location') }}</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="state_id" :class="lbl">{{ __('admin_member_state') }}</label>
                            <select id="state_id" v-model="form.state_id" :class="input">
                                <option value="">{{ __('admin_member_select') }}</option>
                                <option v-for="s in states" :key="s.id" :value="s.id">{{ s.name_en }}</option>
                            </select>
                            <p v-if="err('state_id')" class="mt-1 text-sm text-red-700">{{ err('state_id') }}</p>
                        </div>
                        <div>
                            <label for="district_id" :class="lbl">{{ __('admin_member_district') }}</label>
                            <select id="district_id" v-model="form.district_id" :disabled="!form.state_id" :class="input" class="disabled:bg-gray-100">
                                <option value="">{{ __('admin_member_select') }}</option>
                                <option v-for="d in availableDistricts" :key="d.id" :value="d.id">{{ d.name_en }}</option>
                            </select>
                            <p v-if="err('district_id')" class="mt-1 text-sm text-red-700">{{ err('district_id') }}</p>
                        </div>
                        <div>
                            <label for="city_village" :class="lbl">{{ __('admin_member_city') }}</label>
                            <input id="city_village" v-model="form.city_village" type="text" :class="input" />
                            <p v-if="err('city_village')" class="mt-1 text-sm text-red-700">{{ err('city_village') }}</p>
                        </div>
                        <div>
                            <label for="pin_code" :class="lbl">{{ __('admin_member_pin') }}</label>
                            <input id="pin_code" v-model="form.pin_code" type="text" inputmode="numeric" :class="input" />
                            <p v-if="err('pin_code')" class="mt-1 text-sm text-red-700">{{ err('pin_code') }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="current_address" :class="lbl">{{ __('admin_member_address') }}</label>
                            <textarea id="current_address" v-model="form.current_address" rows="2" :class="input"></textarea>
                            <p v-if="err('current_address')" class="mt-1 text-sm text-red-700">{{ err('current_address') }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="native_place" :class="lbl">{{ __('admin_member_native') }}</label>
                            <input id="native_place" v-model="form.native_place" type="text" :class="input" />
                            <p v-if="err('native_place')" class="mt-1 text-sm text-red-700">{{ err('native_place') }}</p>
                        </div>
                    </div>
                </section>

                <!-- Work -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_work') }}</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="education" :class="lbl">{{ __('admin_member_education') }}</label>
                            <input id="education" v-model="form.education" type="text" :class="input" />
                        </div>
                        <div>
                            <label for="employment_type" :class="lbl">{{ __('admin_member_employment') }}</label>
                            <select id="employment_type" v-model="form.employment_type" :class="input">
                                <option value="">{{ __('admin_member_select') }}</option>
                                <option value="private">Private Job</option>
                                <option value="government">Government Job</option>
                                <option value="business">Business / Self-Employed</option>
                                <option value="student">Student</option>
                                <option value="not_working">Not Working</option>
                                <option v-if="member.employment_type && !['private','government','business','student','not_working'].includes(member.employment_type)" :value="member.employment_type">{{ member.employment_type }}</option>
                            </select>
                        </div>
                        <div>
                            <label for="occupation" :class="lbl">{{ __('admin_member_occupation') }}</label>
                            <input id="occupation" v-model="form.occupation" type="text" :class="input" />
                        </div>
                        <div>
                            <label for="company_name" :class="lbl">{{ __('admin_member_company') }}</label>
                            <input id="company_name" v-model="form.company_name" type="text" :class="input" />
                        </div>
                        <div class="md:col-span-2">
                            <label for="skills" :class="lbl">{{ __('admin_member_skills') }}</label>
                            <input id="skills" v-model="form.skills" type="text" :class="input" />
                        </div>
                    </div>
                </section>

                <!-- Community + social -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_community') }}</h3>
                    <div class="space-y-1">
                        <label :class="check"><input v-model="form.volunteer_interest" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />{{ __('admin_member_volunteer_interest') }}</label>
                        <label :class="check"><input v-model="form.join_district_team" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />{{ __('admin_member_district_team') }}</label>
                    </div>
                    <div class="mt-3">
                        <label for="social_work_interest" :class="lbl">{{ __('admin_member_social_work') }}</label>
                        <input id="social_work_interest" v-model="form.social_work_interest" type="text" :class="input" />
                    </div>
                    <h4 class="mb-2 mt-5 text-sm font-bold text-gray-800">{{ __('admin_member_section_social') }}</h4>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div v-for="[net, netLabel] in [['facebook', 'Facebook'], ['instagram', 'Instagram'], ['linkedin', 'LinkedIn'], ['twitter', 'X (Twitter)'], ['youtube', 'YouTube']]" :key="net">
                            <label :for="net" :class="lbl">{{ netLabel }}</label>
                            <input :id="net" v-model="form[net]" type="url" placeholder="https://" :class="input" />
                            <p v-if="err(net)" class="mt-1 text-sm text-red-700">{{ err(net) }}</p>
                        </div>
                    </div>
                </section>

                <!-- Admin + account -->
                <section :class="box">
                    <h3 class="mb-4 text-base font-bold text-gray-900">{{ __('admin_member_section_admin') }}</h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label for="status" :class="lbl">{{ __('admin_member_profile_status') }}</label>
                            <select id="status" v-model="form.status" :class="input">
                                <option v-for="s in profileStatuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
                            </select>
                            <p v-if="err('status')" class="mt-1 text-sm text-red-700">{{ err('status') }}</p>
                        </div>
                        <div v-if="account">
                            <label for="account_status" :class="lbl">{{ __('admin_member_account_status') }}</label>
                            <select id="account_status" v-model="form.account_status" :disabled="isSelf" :class="input" class="disabled:bg-gray-100">
                                <option v-for="s in accountStatuses" :key="s" :value="s">{{ s.replace(/_/g, ' ') }}</option>
                            </select>
                            <p v-if="isSelf" class="mt-1 text-sm text-gray-500">{{ __('admin_member_own_status') }}</p>
                            <p v-if="err('account_status')" class="mt-1 text-sm text-red-700">{{ err('account_status') }}</p>
                        </div>
                        <div v-if="account">
                            <span :class="lbl">{{ __('admin_member_role') }}</span>
                            <p class="mt-1 flex min-h-11 items-center rounded-lg bg-gray-100 px-3 text-base text-gray-700">{{ (account.role || 'user').replace(/_/g, ' ') }}</p>
                            <p class="mt-1 text-sm text-gray-500">{{ __('admin_member_role_readonly') }}</p>
                        </div>
                        <p v-else class="text-base text-gray-600 md:col-span-2">{{ __('admin_member_no_account') }}</p>
                        <div class="space-y-1 md:col-span-2">
                            <label :class="check"><input v-model="form.is_public" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />{{ __('admin_member_is_public') }}</label>
                            <label :class="check"><input v-model="form.show_mobile" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />{{ __('admin_member_show_mobile') }}</label>
                            <label :class="check"><input v-model="form.allow_whatsapp" type="checkbox" class="h-5 w-5 rounded-sm border-gray-300 text-orange-600" />{{ __('admin_member_allow_whatsapp') }}</label>
                        </div>
                        <div class="md:col-span-2">
                            <label for="admin_note" :class="lbl">{{ __('admin_member_admin_note') }}</label>
                            <textarea id="admin_note" v-model="form.admin_note" rows="3" :class="input"></textarea>
                            <p v-if="err('admin_note')" class="mt-1 text-sm text-red-700">{{ err('admin_note') }}</p>
                        </div>
                    </div>
                </section>

                <div class="sticky bottom-0 -mx-4 flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 bg-white/95 px-4 py-3 sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:px-0">
                    <p v-if="Object.keys(form.errors).length" class="mr-auto text-sm font-semibold text-red-700">{{ Object.values(form.errors).map(e => __(e)).join(' ') }}</p>
                    <Link :href="route('admin.members.show', member.id)" class="inline-flex min-h-11 items-center rounded-lg bg-gray-100 px-5 text-base font-bold text-gray-700 hover:bg-gray-200">{{ __('admin_member_cancel') }}</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 items-center rounded-lg bg-orange-600 px-5 text-base font-bold text-white hover:bg-orange-700 disabled:opacity-60">
                        {{ form.processing ? __('admin_member_saving') : __('admin_member_save') }}
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
