<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    settings: Object
});

const form = useForm({
    facebook_url: props.settings.facebook_url || '',
    instagram_url: props.settings.instagram_url || '',
    youtube_url: props.settings.youtube_url || '',
    whatsapp_group_link: props.settings.whatsapp_group_link || '',
    telegram_url: props.settings.telegram_url || '',
    x_url: props.settings.x_url || '',
    linkedin_url: props.settings.linkedin_url || '',
    contact_email: props.settings.contact_email || '',
    contact_mobile: props.settings.contact_mobile || '',
    announcement_en: props.settings.announcement_en || '',
    announcement_hi: props.settings.announcement_hi || '',
    hero_intro_en: props.settings.hero_intro_en || '',
    hero_intro_hi: props.settings.hero_intro_hi || '',
    about_image_url: props.settings.about_image_url || '',
    about_intro_en: props.settings.about_intro_en || '',
    about_intro_hi: props.settings.about_intro_hi || '',
    about_body_en: props.settings.about_body_en || '',
    about_body_hi: props.settings.about_body_hi || '',
    stat_members: props.settings.stat_members || '',
    stat_districts: props.settings.stat_districts || '',
    stat_volunteers: props.settings.stat_volunteers || '',
    stat_matrimonial: props.settings.stat_matrimonial || '',
    founder_name: props.settings.founder_name || 'Kumar Sachin',
    founder_photo_url: props.settings.founder_photo_url || '',
    founder_photo_upload: null,
    founder_intro_en: props.settings.founder_intro_en || '',
    founder_intro_hi: props.settings.founder_intro_hi || '',
    founder_message_en: props.settings.founder_message_en || '',
    founder_message_hi: props.settings.founder_message_hi || '',
    founder_note_en: props.settings.founder_note_en || '',
    founder_note_hi: props.settings.founder_note_hi || '',
    founder_whatsapp_url: props.settings.founder_whatsapp_url || '',
    founder_facebook_url: props.settings.founder_facebook_url || '',
    founder_instagram_url: props.settings.founder_instagram_url || '',
    founder_profile_url: props.settings.founder_profile_url || '',
});

const founderPhotoPreview = ref(props.settings.founder_photo_url || '');

const selectFounderPhoto = (event) => {
    const file = event.target.files[0];
    form.founder_photo_upload = file || null;

    if (file) {
        founderPhotoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            form.founder_photo_upload = null;
        },
    });
};
</script>

<template>
    <Head title="Website Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Website Settings & Configuration</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-8">
                            
                            <!-- Contact Details -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Global Contact Details</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Official Email</label>
                                        <input type="email" v-model="form.contact_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Official Mobile/WhatsApp Number</label>
                                        <input type="text" v-model="form.contact_mobile" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Homepage Bilingual Content -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Homepage Bilingual Content</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Announcement (English)</label>
                                        <textarea v-model="form.announcement_en" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Announcement (Hindi)</label>
                                        <textarea v-model="form.announcement_hi" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Hero Introduction (English)</label>
                                        <textarea v-model="form.hero_intro_en" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Hero Introduction (Hindi)</label>
                                        <textarea v-model="form.hero_intro_hi" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">About Section Image URL</label>
                                        <input type="url" v-model="form.about_image_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">About Intro (English)</label>
                                        <textarea v-model="form.about_intro_en" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">About Intro (Hindi)</label>
                                        <textarea v-model="form.about_intro_hi" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">About Body (English)</label>
                                        <textarea v-model="form.about_body_en" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">About Body (Hindi)</label>
                                        <textarea v-model="form.about_body_hi" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Media Links -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Social Media Links</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Facebook Page URL</label>
                                        <input type="url" v-model="form.facebook_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Instagram URL</label>
                                        <input type="url" v-model="form.instagram_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">YouTube Channel URL</label>
                                        <input type="url" v-model="form.youtube_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">WhatsApp Group Invite Link</label>
                                        <input type="url" v-model="form.whatsapp_group_link" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Telegram URL</label>
                                        <input type="url" v-model="form.telegram_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">X (Twitter) URL</label>
                                        <input type="url" v-model="form.x_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
                                        <input type="url" v-model="form.linkedin_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Homepage Statistics -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Homepage Statistics (Displayed to Public)</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Members Connected</label>
                                        <input type="number" v-model="form.stat_members" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Districts Covered</label>
                                        <input type="number" v-model="form.stat_districts" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Volunteers</label>
                                        <input type="number" v-model="form.stat_volunteers" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Matrimonial Profiles</label>
                                        <input type="number" v-model="form.stat_matrimonial" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">These numbers are displayed on the trust cards on the main homepage. Leave a field blank to show the live count.</p>
                            </div>

                            <!-- Founder Message -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Founder Message</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Name</label>
                                        <input type="text" v-model="form.founder_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div class="md:col-span-2 rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
                                        <label class="block text-sm font-bold text-blue-950">Founder Photo</label>
                                        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-center">
                                            <div class="flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-3xl border-4 border-orange-400 bg-white text-2xl font-black text-blue-950 shadow-xs">
                                                <img v-if="founderPhotoPreview" :src="founderPhotoPreview" alt="Founder photo preview" class="h-full w-full object-cover">
                                                <span v-else>KC</span>
                                            </div>
                                            <div class="flex-1">
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    @input="selectFounderPhoto"
                                                    class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-full file:border-0 file:bg-orange-500 file:px-5 file:py-2.5 file:text-sm file:font-black file:text-white hover:file:bg-orange-600"
                                                >
                                                <p class="mt-2 text-sm font-medium text-slate-600">Upload JPG, PNG, or WebP up to 4MB. The uploaded photo will be shown on the homepage founder section.</p>
                                                <div v-if="form.errors.founder_photo_upload" class="mt-2 text-sm font-bold text-red-600">{{ form.errors.founder_photo_upload }}</div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700">Founder Photo URL fallback</label>
                                            <input type="url" v-model="form.founder_photo_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <p class="mt-1 text-xs font-medium text-slate-500">Use this only when the image is hosted somewhere else.</p>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Intro (English)</label>
                                        <textarea v-model="form.founder_intro_en" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Intro (Hindi)</label>
                                        <textarea v-model="form.founder_intro_hi" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Message (English)</label>
                                        <textarea v-model="form.founder_message_en" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Message (Hindi)</label>
                                        <textarea v-model="form.founder_message_hi" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Closing Note (English)</label>
                                        <textarea v-model="form.founder_note_en" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Closing Note (Hindi)</label>
                                        <textarea v-model="form.founder_note_hi" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder WhatsApp URL</label>
                                        <input type="url" v-model="form.founder_whatsapp_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Facebook URL</label>
                                        <input type="url" v-model="form.founder_facebook_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Instagram URL</label>
                                        <input type="url" v-model="form.founder_instagram_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Founder Profile URL</label>
                                        <input type="url" v-model="form.founder_profile_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end pt-4">
                                <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-6 py-2 rounded-sm shadow-sm hover:bg-indigo-700 disabled:opacity-50">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
