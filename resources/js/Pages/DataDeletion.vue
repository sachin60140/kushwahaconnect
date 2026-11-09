<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const page = usePage();
const success = computed(() => page.props.flash?.success);

const form = useForm({
    name: '',
    mobile: '',
    email: '',
    reason: '',
});

const submit = () => {
    form.post(route('data-deletion.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Data Deletion Request - KushwahaConnect" />

    <PublicLayout :show-flash="false">
        <section class="kc-hero">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(249,115,22,0.24),transparent_34%)]"></div>
            <div class="kc-hero-inner">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Privacy & Data</p>
                    <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">
                        Request Account or Profile Data Deletion
                    </h1>
                    <p class="mt-5 text-lg leading-8 text-blue-100">
                        Submit this form if you want KushwahaConnect to delete your account, community profile, matrimonial profile, or submitted personal data after verification.
                    </p>
                </div>
            </div>
        </section>

        <section class="kc-page">
            <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[0.9fr_1.1fr]">
                <aside class="kc-surface p-6 sm:p-8">
                    <p class="kc-eyebrow">Before You Submit</p>
                    <h2 class="mt-3 text-2xl font-black text-blue-950">How deletion works</h2>
                    <div class="mt-6 space-y-4 text-sm leading-6 text-slate-600">
                        <p>Our team will verify your registered mobile number or email before processing the request.</p>
                        <p>Approved deletion may remove your login account, community profile, matrimonial profile, uploaded photos/documents, and related public visibility where applicable.</p>
                        <p>Some records may be retained if required for security, fraud prevention, dispute handling, legal compliance, or platform audit history.</p>
                    </div>
                    <div class="mt-7 rounded-2xl bg-orange-50 p-4 text-sm font-bold leading-6 text-orange-900">
                        For faster verification, use the same mobile number or email used during registration.
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <Link :href="route('privacy')" class="kc-btn-secondary">Privacy Policy</Link>
                        <Link :href="route('terms')" class="kc-btn-secondary">Terms</Link>
                    </div>
                </aside>

                <div class="kc-surface p-6 sm:p-8">
                    <div v-if="success" class="mb-6 rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-800">
                        {{ success }}
                    </div>

                    <form class="space-y-6" @submit.prevent="submit">
                        <div>
                            <label for="name" class="block text-sm font-black text-blue-950">Full Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                required
                                class="mt-2 block w-full"
                                placeholder="Enter your full name"
                            >
                            <p v-if="form.errors.name" class="mt-2 text-sm font-bold text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <label for="mobile" class="block text-sm font-black text-blue-950">Registered Mobile Number</label>
                                <input
                                    id="mobile"
                                    v-model="form.mobile"
                                    type="text"
                                    required
                                    class="mt-2 block w-full"
                                    placeholder="Mobile number"
                                >
                                <p v-if="form.errors.mobile" class="mt-2 text-sm font-bold text-red-600">{{ form.errors.mobile }}</p>
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-black text-blue-950">Email Address</label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-2 block w-full"
                                    placeholder="Optional email"
                                >
                                <p v-if="form.errors.email" class="mt-2 text-sm font-bold text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="reason" class="block text-sm font-black text-blue-950">What should be deleted?</label>
                            <textarea
                                id="reason"
                                v-model="form.reason"
                                rows="5"
                                class="mt-2 block w-full"
                                placeholder="Example: Please delete my account and community profile / matrimonial profile / contact enquiry data."
                            ></textarea>
                            <p v-if="form.errors.reason" class="mt-2 text-sm font-bold text-red-600">{{ form.errors.reason }}</p>
                        </div>

                        <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                            By submitting this request, you confirm that the details belong to you or that you are authorized to request deletion for the person/profile mentioned.
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <button type="submit" :disabled="form.processing" class="kc-btn-primary">
                                Submit Deletion Request
                            </button>
                            <Link href="/contact" class="kc-btn-secondary">Contact Support</Link>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
