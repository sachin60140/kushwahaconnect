<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    item: Object
});

const form = useForm({
    _method: 'PUT',
    title: props.item.title,
    content: props.item.content,
    type: props.item.type,
    event_date: props.item.event_date_local || '',
    event_location: props.item.event_location || '',
    registration_url: props.item.registration_url || '',
    image: null,
    is_published: props.item.is_published
});

const submit = () => {
    form.post(route('admin.news.update', props.item.id));
};
</script>

<template>
    <Head title="Edit News/Event" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit News / Event</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="news">News</option>
                                    <option value="event">Event</option>
                                    <option value="success_story">Success Story</option>
                                    <option value="update">Update</option>
                                </select>
                                <div v-if="form.errors.type" class="text-red-500 text-xs mt-1">{{ form.errors.type }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" v-model="form.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                            </div>
                            
                            <div v-if="form.type === 'event'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Event Date & Time <span class="font-normal text-gray-500">(India time, IST)</span></label>
                                    <input type="datetime-local" v-model="form.event_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.event_date" class="text-red-500 text-xs mt-1">{{ form.errors.event_date }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location</label>
                                    <input type="text" v-model="form.event_location" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.event_location" class="text-red-500 text-xs mt-1">{{ form.errors.event_location }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Registration URL</label>
                                    <input type="url" v-model="form.registration_url" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="form.errors.registration_url" class="text-red-500 text-xs mt-1">{{ form.errors.registration_url }}</div>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Content</label>
                                <textarea v-model="form.content" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                <div v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}</div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Image</label>
                                <div v-if="item.image_url" class="mb-2">
                                    <img :src="item.image_url" class="h-32 object-contain" alt="Current Image">
                                </div>
                                <input type="file" @input="form.image = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                <div v-if="form.errors.image" class="text-red-500 text-xs mt-1">{{ form.errors.image }}</div>
                            </div>
                            
                            <div class="flex items-center">
                                <input id="is_published" type="checkbox" v-model="form.is_published" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded-sm">
                                <label for="is_published" class="ml-2 block text-sm text-gray-900"> Published </label>
                            </div>

                            <div class="flex justify-end gap-4">
                                <Link :href="route('admin.news.index')" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-sm shadow-xs hover:bg-gray-50">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-indigo-600 text-white px-4 py-2 rounded-sm shadow-sm hover:bg-indigo-700 disabled:opacity-50">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
