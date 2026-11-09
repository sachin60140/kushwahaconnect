<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    requests: Object,
    filters: Object,
    statuses: Object,
});

const filterForm = useForm({
    status: props.filters?.status || '',
    profile_type: props.filters?.profile_type || '',
});

const updateForm = useForm({
    status: '',
    admin_note: '',
});

const submitFilters = () => {
    filterForm.get(route('admin.corrections.index'), {
        preserveState: true,
        replace: true,
    });
};

const updateRequest = (requestItem) => {
    updateForm.status = requestItem.status;
    updateForm.admin_note = requestItem.admin_note || '';
    updateForm.patch(route('admin.corrections.update', requestItem.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-black text-blue-950">Correction Requests</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">Verify sources before changing public information.</p>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submitFilters" class="kc-surface mb-6 grid gap-4 p-4 md:grid-cols-3">
                <select v-model="filterForm.profile_type">
                    <option value="">All Profile Types</option>
                    <option value="personality">Personality</option>
                    <option value="representative">Representative</option>
                </select>
                <select v-model="filterForm.status">
                    <option value="">All Statuses</option>
                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                </select>
                <button class="kc-btn-blue" type="submit">Apply Filters</button>
            </form>

            <div class="space-y-5">
                <article v-for="requestItem in requests.data" :key="requestItem.id" class="kc-surface p-5">
                    <div class="grid gap-5 lg:grid-cols-[1.2fr_0.8fr]">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">{{ requestItem.profile_type }}</span>
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-800">{{ statuses[requestItem.status] || requestItem.status }}</span>
                            </div>
                            <h3 class="mt-3 text-2xl font-black text-blue-950">{{ requestItem.profile_name || 'Profile correction' }}</h3>
                            <p class="mt-1 text-sm font-bold text-slate-600">Requested by {{ requestItem.requester_name }}</p>
                            <p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ requestItem.correction_details }}</p>
                            <p v-if="requestItem.source_links" class="mt-4 whitespace-pre-wrap rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                                <strong>Sources:</strong> {{ requestItem.source_links }}
                            </p>
                            <div class="mt-4 text-sm text-slate-500">
                                <p v-if="requestItem.requester_mobile">Mobile: {{ requestItem.requester_mobile }}</p>
                                <p v-if="requestItem.requester_email">Email: {{ requestItem.requester_email }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-2xl border border-slate-100 bg-white p-4">
                            <label class="block text-sm font-black text-blue-950">Review status</label>
                            <select v-model="requestItem.status">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <label class="block text-sm font-black text-blue-950">Admin note</label>
                            <textarea v-model="requestItem.admin_note" rows="5"></textarea>
                            <button @click="updateRequest(requestItem)" type="button" class="kc-btn-primary w-full">Update Request</button>
                        </div>
                    </div>
                </article>
                <div v-if="requests.data.length === 0" class="kc-surface p-8 text-center font-bold text-slate-500">No correction requests found.</div>
            </div>

            <div v-if="requests.links && requests.links.length > 3" class="mt-6 flex flex-wrap">
                <template v-for="(link, p) in requests.links" :key="p">
                    <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-sm bg-white" v-html="link.label" />
                    <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-sm hover:bg-white" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'bg-white': !link.active }" :href="link.url" v-html="link.label" />
                </template>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
