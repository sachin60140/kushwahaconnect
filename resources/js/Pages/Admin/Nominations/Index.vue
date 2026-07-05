<script setup>
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    nominations: Object,
    filters: Object,
    statuses: Object,
});

const filterForm = useForm({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
});

const updateForm = useForm({
    status: '',
    admin_note: '',
});

const deleteForm = useForm({});

const submitFilters = () => {
    filterForm.get(route('admin.nominations.index'), {
        preserveState: true,
        replace: true,
    });
};

const updateNomination = (nomination) => {
    updateForm.status = nomination.status;
    updateForm.admin_note = nomination.admin_note || '';
    updateForm.patch(route('admin.nominations.update', nomination.id), {
        preserveScroll: true,
    });
};

const deleteNomination = (nomination) => {
    if (confirm(`Delete nomination for ${nomination.person_name}?`)) {
        deleteForm.delete(route('admin.nominations.destroy', nomination.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-2xl font-black text-blue-950">Personality Nominations</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">All nominations require admin approval before publication.</p>
            </div>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submitFilters" class="kc-surface mb-6 grid gap-4 p-4 md:grid-cols-4">
                <input v-model="filterForm.search" type="search" placeholder="Search person or nominator" class="md:col-span-2">
                <select v-model="filterForm.status">
                    <option value="">All Statuses</option>
                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                </select>
                <button class="kc-btn-blue" type="submit">Apply Filters</button>
            </form>

            <div class="space-y-5">
                <article v-for="nomination in nominations.data" :key="nomination.id" class="kc-surface p-5">
                    <div class="grid gap-5 lg:grid-cols-[1.3fr_0.7fr]">
                        <div>
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">{{ nomination.category }}</span>
                                <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-800">{{ statuses[nomination.status] || nomination.status }}</span>
                            </div>
                            <h3 class="mt-3 text-2xl font-black text-blue-950">{{ nomination.person_name }}</h3>
                            <p class="mt-1 text-sm font-bold text-slate-600">{{ nomination.profession_or_designation }}</p>
                            <p class="mt-2 text-sm text-slate-500">{{ [nomination.district, nomination.state].filter(Boolean).join(', ') }}</p>
                            <p class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-700">{{ nomination.achievement }}</p>
                            <p v-if="nomination.biography" class="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{{ nomination.biography }}</p>
                            <div class="mt-4 grid gap-2 text-sm font-bold text-orange-700 sm:grid-cols-2">
                                <a v-if="nomination.photograph_url" :href="nomination.photograph_url" target="_blank">View photograph</a>
                                <a v-if="nomination.supporting_document_url" :href="nomination.supporting_document_url" target="_blank">View supporting document</a>
                            </div>
                            <div class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                                <p><strong>Nominator:</strong> {{ nomination.nominator_name }} - {{ nomination.nominator_mobile }}</p>
                                <p><strong>Relationship:</strong> {{ nomination.relationship || 'N/A' }}</p>
                                <p v-if="nomination.source_links" class="mt-2 whitespace-pre-wrap"><strong>Sources:</strong> {{ nomination.source_links }}</p>
                                <p v-if="nomination.social_links" class="mt-2 whitespace-pre-wrap"><strong>Social:</strong> {{ nomination.social_links }}</p>
                            </div>
                        </div>

                        <div class="space-y-4 rounded-2xl border border-slate-100 bg-white p-4">
                            <label class="block text-sm font-black text-blue-950">Review status</label>
                            <select v-model="nomination.status">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <label class="block text-sm font-black text-blue-950">Admin note</label>
                            <textarea v-model="nomination.admin_note" rows="5"></textarea>
                            <button @click="updateNomination(nomination)" type="button" class="kc-btn-primary w-full">Update Status</button>
                            <button @click="deleteNomination(nomination)" type="button" class="kc-btn-secondary w-full text-red-700">Delete</button>
                        </div>
                    </div>
                </article>
                <div v-if="nominations.data.length === 0" class="kc-surface p-8 text-center font-bold text-slate-500">No nominations found.</div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
