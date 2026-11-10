<script setup>
import MobileOtpField from '@/Components/MobileOtpField.vue';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import { useLanguage } from '@/composables/useLanguage';

defineProps({
    categories: Array,
});

const { locale, __ } = useLanguage();
const isHindi = computed(() => locale.value === 'hi');

const text = computed(() => ({
    title: isHindi.value ? 'किसी प्रेरक व्यक्ति का नाम सुझाएँ' : 'Nominate an Inspiring Person',
    intro: isHindi.value
        ? 'सभी नामांकन admin review के बाद ही प्रकाशित होंगे। कृपया सत्यापन स्रोत और सहमति जरूर दें।'
        : 'Every nomination is reviewed by admins before publication. Please include verification sources and consent.',
    submit: isHindi.value ? 'नामांकन जमा करें' : 'Submit Nomination',
    consent: isHindi.value
        ? 'मैं पुष्टि करता/करती हूं कि दी गई जानकारी सत्यापन के लिए साझा की जा सकती है और प्रकाशन से पहले admin review आवश्यक है।'
        : 'I confirm the information may be reviewed for verification and will require admin approval before publication.',
}));

const form = useForm({
    person_name: '',
    category: '',
    profession_or_designation: '',
    state: '',
    district: '',
    achievement: '',
    biography: '',
    photograph: null,
    supporting_document: null,
    source_links: '',
    social_links: '',
    nominator_name: '',
    nominator_mobile: '',
    relationship: '',
    consent: false,
});

const submit = () => {
    form.post(route('nominations.store'), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <SeoMeta title="Nominate an Inspiring Person" description="Suggest a verified inspiring personality for Kushwaha Pride." />

    <PublicLayout :show-flash="false">
        <section class="kc-hero">
            <div class="kc-hero-inner">
                <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Kushwaha Pride</p>
                <h1 class="mt-3 text-4xl font-black tracking-tight sm:text-5xl">{{ text.title }}</h1>
                <p class="mt-5 max-w-3xl text-lg leading-8 text-blue-100">{{ text.intro }}</p>
            </div>
        </section>

        <section class="kc-page">
            <div class="kc-container max-w-4xl">
                <form @submit.prevent="submit" class="kc-surface space-y-6 p-6">
                    <div v-if="$page.props.flash?.success" class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm font-bold text-green-800">
                        {{ $page.props.flash.success }}
                    </div>

                    <div v-if="Object.keys(form.errors).length" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            <li v-for="(message, field) in form.errors" :key="field">{{ __(message) }}</li>
                        </ul>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-blue-950">Person name</label>
                            <input v-model="form.person_name" type="text" required class="mt-1 w-full">
                            <p v-if="form.errors.person_name" class="mt-1 text-sm font-bold text-red-600">{{ form.errors.person_name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">Category</label>
                            <select v-model="form.category" required class="mt-1 w-full">
                                <option value="">Select category</option>
                                <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm font-bold text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">Profession or designation</label>
                            <input v-model="form.profession_or_designation" type="text" class="mt-1 w-full">
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">Relationship</label>
                            <input v-model="form.relationship" type="text" class="mt-1 w-full">
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">State</label>
                            <input v-model="form.state" type="text" class="mt-1 w-full">
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">District</label>
                            <input v-model="form.district" type="text" class="mt-1 w-full">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-blue-950">Achievement</label>
                        <textarea v-model="form.achievement" rows="4" required class="mt-1 w-full"></textarea>
                        <p v-if="form.errors.achievement" class="mt-1 text-sm font-bold text-red-600">{{ form.errors.achievement }}</p>
                    </div>

                    <div>
                        <label class="text-sm font-black text-blue-950">Biography</label>
                        <textarea v-model="form.biography" rows="5" class="mt-1 w-full"></textarea>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-blue-950">Photograph</label>
                            <input type="file" accept="image/*" @input="form.photograph = $event.target.files[0]" class="mt-1 block w-full">
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">Supporting document</label>
                            <input type="file" accept="image/*,application/pdf" @input="form.supporting_document = $event.target.files[0]" class="mt-1 block w-full">
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-blue-950">Source links</label>
                        <textarea v-model="form.source_links" rows="3" class="mt-1 w-full" placeholder="Official website, news article, government source, public profile"></textarea>
                    </div>

                    <div>
                        <label class="text-sm font-black text-blue-950">Social-media links</label>
                        <textarea v-model="form.social_links" rows="3" class="mt-1 w-full"></textarea>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-black text-blue-950">Nominator name</label>
                            <input v-model="form.nominator_name" type="text" required class="mt-1 w-full">
                        </div>
                        <div>
                            <label class="text-sm font-black text-blue-950">Nominator mobile</label>
                            <input v-model="form.nominator_mobile" type="tel" inputmode="numeric" autocomplete="tel" required class="mt-1 w-full">
                            <MobileOtpField :mobile="form.nominator_mobile" :error="form.errors.nominator_mobile" id-prefix="nominator-mobile-otp" />
                        </div>
                    </div>

                    <label class="flex gap-3 rounded-2xl bg-orange-50 p-4 text-sm font-bold text-slate-700">
                        <input v-model="form.consent" type="checkbox" required class="mt-1">
                        <span>{{ text.consent }}</span>
                    </label>
                    <p v-if="form.errors.consent" class="text-sm font-bold text-red-600">{{ form.errors.consent }}</p>

                    <button type="submit" :disabled="form.processing" class="kc-btn-primary">{{ text.submit }}</button>
                </form>
            </div>
        </section>
    </PublicLayout>
</template>
