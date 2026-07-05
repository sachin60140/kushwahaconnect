<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    profile: Object,
    categories: Array,
});

const form = useForm({
    _method: props.profile ? 'PUT' : 'POST',
    name_hi: props.profile?.name_hi || '',
    name_en: props.profile?.name_en || '',
    photo: null,
    category: props.profile?.category || '',
    designation_hi: props.profile?.designation_hi || '',
    designation_en: props.profile?.designation_en || '',
    state: props.profile?.state || '',
    district: props.profile?.district || '',
    city: props.profile?.city || '',
    biography_hi: props.profile?.biography_hi || '',
    biography_en: props.profile?.biography_en || '',
    achievements_hi: props.profile?.achievements_hi || '',
    achievements_en: props.profile?.achievements_en || '',
    organisation: props.profile?.organisation || '',
    official_website: props.profile?.official_website || '',
    facebook_url: props.profile?.facebook_url || '',
    instagram_url: props.profile?.instagram_url || '',
    linkedin_url: props.profile?.linkedin_url || '',
    youtube_url: props.profile?.youtube_url || '',
    x_url: props.profile?.x_url || '',
    verification_sources: props.profile?.verification_sources || '',
    last_verified_at: props.profile?.last_verified_at ? props.profile.last_verified_at.slice(0, 10) : '',
    is_verified: props.profile?.is_verified || false,
    is_featured: props.profile?.is_featured || false,
    display_order: props.profile?.display_order || 0,
    is_published: props.profile?.is_published || false,
});

const submit = () => {
    const routeName = props.profile ? route('admin.pride.update', props.profile.id) : route('admin.pride.store');
    form.post(routeName, { forceFormData: true });
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-black text-blue-950">{{ profile ? 'Edit Kushwaha Pride Profile' : 'Add Kushwaha Pride Profile' }}</h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submit" class="kc-surface mx-auto max-w-5xl space-y-8 p-6">
                <section>
                    <h3 class="text-lg font-black text-blue-950">Identity</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <input v-model="form.name_hi" type="text" placeholder="Hindi name" required>
                        <input v-model="form.name_en" type="text" placeholder="English name" required>
                        <select v-model="form.category" required>
                            <option value="">Select category</option>
                            <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                        </select>
                        <input type="file" accept="image/*" @input="form.photo = $event.target.files[0]">
                        <input v-model="form.designation_hi" type="text" placeholder="Hindi designation">
                        <input v-model="form.designation_en" type="text" placeholder="English designation">
                        <input v-model="form.organisation" type="text" placeholder="Organisation">
                        <input v-model="form.official_website" type="url" placeholder="Official website">
                    </div>
                    <img v-if="profile?.photo_url" :src="profile.photo_url" alt="Current profile photo" class="mt-4 h-24 w-24 rounded-2xl object-cover">
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Location</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-3">
                        <input v-model="form.state" type="text" placeholder="State">
                        <input v-model="form.district" type="text" placeholder="District">
                        <input v-model="form.city" type="text" placeholder="City">
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Bilingual Content</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <textarea v-model="form.biography_hi" rows="5" placeholder="Hindi biography"></textarea>
                        <textarea v-model="form.biography_en" rows="5" placeholder="English biography"></textarea>
                        <textarea v-model="form.achievements_hi" rows="5" placeholder="Hindi achievements"></textarea>
                        <textarea v-model="form.achievements_en" rows="5" placeholder="English achievements"></textarea>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Social and Verification</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <input v-model="form.facebook_url" type="url" placeholder="Facebook URL">
                        <input v-model="form.instagram_url" type="url" placeholder="Instagram URL">
                        <input v-model="form.linkedin_url" type="url" placeholder="LinkedIn URL">
                        <input v-model="form.youtube_url" type="url" placeholder="YouTube URL">
                        <input v-model="form.x_url" type="url" placeholder="X/Twitter URL">
                        <input v-model="form.last_verified_at" type="date" placeholder="Last verified date">
                        <textarea v-model="form.verification_sources" rows="4" placeholder="Verification sources" class="md:col-span-2"></textarea>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-4">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_verified" type="checkbox"> Verified</label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_featured" type="checkbox"> Homepage featured</label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_published" type="checkbox"> Published</label>
                    <input v-model="form.display_order" type="number" min="0" placeholder="Display order">
                </section>

                <div class="rounded-2xl bg-blue-50 p-4 text-sm font-bold text-blue-900">
                    Neutral labels only: Inspiring Personality, Verified Community Personality. Do not use Best, Number One, or Most Powerful.
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.pride.index')" class="kc-btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="kc-btn-primary">Save Profile</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
