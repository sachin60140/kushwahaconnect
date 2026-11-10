<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { useLanguage } from '@/composables/useLanguage';

defineProps({
    states: Array,
});

const { __ } = useLanguage();
const page = usePage();
const settings = computed(() => page.props.websiteSettings || {});

const form = useForm({
    name: '',
    mobile: '',
    whatsapp: '',
    email: '',
    state_id: '',
    district_id: '',
    purpose: '',
    message: '',
});

const submit = () => {
    form.post(route('contact.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Contact Us - KushwahaConnect" />

    <PublicLayout :show-flash="false">
        <section class="kc-hero">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.26),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(21,128,61,0.22),transparent_34%)]"></div>
            <div class="kc-hero-inner text-center">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">{{ __('menu_contact') }}</p>
                <h1 class="mx-auto mt-4 max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl">
                    Contact KushwahaConnect
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-blue-100">
                    We are here for community support, platform help, partnerships, and feedback.
                </p>
            </div>
        </section>

        <section class="kc-page">
            <div class="kc-container grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                <aside class="space-y-5">
                    <div class="kc-panel p-6">
                        <p class="kc-eyebrow">Contact Details</p>
                        <h2 class="mt-2 text-2xl font-black text-blue-950">Reach the team</h2>
                        <div class="mt-5 space-y-3 text-sm font-bold text-slate-600">
                            <p v-if="settings.contact_email">{{ settings.contact_email }}</p>
                            <p v-if="settings.contact_mobile">{{ settings.contact_mobile }}</p>
                            <p v-if="!settings.contact_email && !settings.contact_mobile">Use the form and our team will respond as soon as possible.</p>
                        </div>
                    </div>
                    <div class="rounded-3xl bg-green-900 p-6 text-white shadow-xl shadow-green-950/15">
                        <p class="text-sm font-black uppercase tracking-[0.22em] text-green-200">{{ __('help_title') }}</p>
                        <p class="mt-3 text-base leading-7 text-green-50">
                            For urgent medical, education, job, or family support, use Help Each Other so volunteers can review your request.
                        </p>
                    </div>
                </aside>

                <div class="kc-surface overflow-hidden">
                    <div class="border-b border-slate-100 bg-blue-950 px-6 py-7 text-white sm:px-8">
                        <h2 class="text-2xl font-black text-orange-300">Send Message</h2>
                        <p class="mt-2 text-blue-100">Share your details and reason for contact.</p>
                    </div>

                    <div v-if="$page.props.flash?.success" class="m-6 rounded-2xl border border-green-100 bg-green-50 p-4 text-sm font-bold text-green-800">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-6 p-6 sm:p-8">
                        <div v-if="Object.keys(form.errors).length" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                <li v-for="(message, field) in form.errors" :key="field">{{ __(message) }}</li>
                            </ul>
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-sm font-black text-blue-950">Name *</label>
                                <input v-model="form.name" type="text" class="w-full" required>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-black text-blue-950">Email</label>
                                <input v-model="form.email" type="email" class="w-full">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-black text-blue-950">Mobile Number *</label>
                                <input v-model="form.mobile" type="tel" inputmode="numeric" autocomplete="tel" class="w-full" required>
                                <MobileOtpField :mobile="form.mobile" :error="form.errors.mobile" id-prefix="contact-mobile-otp" />
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-black text-blue-950">WhatsApp Number</label>
                                <input v-model="form.whatsapp" type="text" class="w-full">
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-black text-blue-950">Purpose of Contact</label>
                                <select v-model="form.purpose" class="w-full">
                                    <option value="">Select Purpose</option>
                                    <option value="Join Community">Join Community</option>
                                    <option value="Matrimonial">Matrimonial</option>
                                    <option value="Volunteer">Volunteer</option>
                                    <option value="Help Request">Help Request</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="mb-1 block text-sm font-black text-blue-950">Message *</label>
                                <textarea v-model="form.message" rows="5" class="w-full" required></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing" class="kc-btn-primary">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
