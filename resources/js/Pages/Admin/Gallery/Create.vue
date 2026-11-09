<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, ref } from 'vue';

const form = useForm({
    title: '',
    description: '',
    is_published: true,
    images: []
});

const previewImages = ref([]);

// Laravel reports per-file failures as `images.0`, `images.1`, ...
const imageErrors = computed(() => Object.entries(form.errors)
    .filter(([field]) => field === 'images' || field.startsWith('images.'))
    .map(([, message]) => message));

const handleImageUpload = (event) => {
    const files = Array.from(event.target.files);
    form.images = files;
    
    // Create previews
    previewImages.value = [];
    files.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImages.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });
};

const submit = () => {
    form.post(route('admin.gallery.store'));
};
</script>

<template>
    <Head title="Create Gallery" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Gallery Album</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Album Title <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Optional description of the event..."></textarea>
                                <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Photos <span class="text-gray-500 text-xs">(Select multiple files)</span></label>
                                <input type="file" @change="handleImageUpload" multiple accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-2">
                                <div v-if="imageErrors.length" class="text-red-500 text-xs mt-1">
                                    <p v-for="(message, index) in imageErrors" :key="index">{{ message }}</p>
                                </div>
                                
                                <div v-if="previewImages.length > 0" class="mt-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-4">
                                    <div v-for="(preview, index) in previewImages" :key="index" class="relative aspect-w-1 aspect-h-1 rounded-lg overflow-hidden border border-gray-200 shadow-sm h-24">
                                        <img :src="preview" class="object-cover w-full h-full">
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center pt-2">
                                <input id="is_published" type="checkbox" v-model="form.is_published" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_published" class="ml-2 block text-sm text-gray-900">
                                    Publish immediately
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 border-t pt-4">
                                <Link :href="route('admin.gallery.index')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded shadow-sm hover:bg-gray-50">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 disabled:opacity-50 flex items-center">
                                    <span v-if="form.processing" class="mr-2">Uploading...</span>
                                    <span>Create & Upload Photos</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
