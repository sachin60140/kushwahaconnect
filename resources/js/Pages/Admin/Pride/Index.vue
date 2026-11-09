<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    profiles: Object,
    filters: Object,
    categories: Array,
});

const filterForm = useForm({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    status: props.filters?.status || '',
});

const archiveForm = useForm({});

const submitFilters = () => {
    filterForm.get(route('admin.pride.index'), {
        preserveState: true,
        replace: true,
    });
};

const archiveProfile = (profile) => {
    if (confirm(`Archive ${profile.name_en}?`)) {
        archiveForm.patch(route('admin.pride.archive', profile.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-2xl font-black text-blue-950">Kushwaha Pride</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Verified community personalities only. Avoid subjective ranking labels.</p>
                </div>
                <Link :href="route('admin.pride.create')" class="kc-btn-primary">Add Profile</Link>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submitFilters" class="kc-surface mb-6 grid gap-4 p-4 md:grid-cols-4">
                <input v-model="filterForm.search" type="search" placeholder="Search name, designation, organisation" class="md:col-span-2">
                <select v-model="filterForm.category">
                    <option value="">All Categories</option>
                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                </select>
                <select v-model="filterForm.status">
                    <option value="">Active Records</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
                <button class="kc-btn-blue md:col-span-4" type="submit">Apply Filters</button>
            </form>

            <div class="kc-table overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Profile</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Category</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Verification</th>
                            <th class="px-5 py-3 text-left text-xs font-black uppercase text-slate-500">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-black uppercase text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="profile in profiles.data" :key="profile.id">
                            <td class="px-5 py-4">
                                <div class="font-black text-blue-950">{{ profile.name_en }}</div>
                                <div class="text-sm font-bold text-slate-500">{{ profile.designation_en || profile.organisation }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-700">{{ profile.category }}</td>
                            <td class="px-5 py-4 text-sm">
                                <span class="rounded-full px-3 py-1 text-xs font-black" :class="profile.is_verified ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600'">
                                    {{ profile.is_verified ? 'Verified' : 'Unverified' }}
                                </span>
                                <div class="mt-1 text-xs text-slate-500">{{ profile.last_verified_at ? new Date(profile.last_verified_at).toLocaleDateString() : 'No date' }}</div>
                            </td>
                            <td class="px-5 py-4 text-sm">
                                <span class="rounded-full px-3 py-1 text-xs font-black" :class="profile.archived_at ? 'bg-slate-100 text-slate-600' : profile.is_published ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-700'">
                                    {{ profile.archived_at ? 'Archived' : profile.is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-bold">
                                <Link :href="route('admin.pride.edit', profile.id)" class="text-blue-700 hover:text-blue-950">Edit</Link>
                                <button v-if="!profile.archived_at" type="button" @click="archiveProfile(profile)" class="ml-4 text-red-600 hover:text-red-800">Archive</button>
                            </td>
                        </tr>
                        <tr v-if="profiles.data.length === 0">
                            <td colspan="5" class="px-5 py-8 text-center font-bold text-slate-500">No profiles found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="profiles.links && profiles.links.length > 3" class="mt-6 flex flex-wrap">
                <template v-for="(link, p) in profiles.links" :key="p">
                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded bg-white" v-html="link.label" />
                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white': !link.active }" :href="link.url" v-html="link.label" />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
