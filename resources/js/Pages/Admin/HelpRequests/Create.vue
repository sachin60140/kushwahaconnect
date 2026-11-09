<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    categories: Array,
    urgencies: Array,
    statuses: Array,
});

// Admin creates the request on behalf of the person who needs help.
const form = useForm({
    name: '',
    mobile: '',
    whatsapp: '',
    state: '',
    district: '',
    city: '',
    category: props.categories[0] ?? 'Other',
    title: '',
    description: '',
    urgency: 'Normal',
    status: 'Approved',
    is_public: true,
});

const sameAsMobile = () => {
    form.whatsapp = form.mobile;
};

const submit = () => {
    form.post(route('admin.help-requests.store'), { preserveScroll: true });
};

const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-orange-500 focus:ring-orange-500 sm:text-sm';
</script>

<template>
    <Head title="Create Help Request" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Help Request</h2>
                    <p class="mt-1 text-sm text-gray-500">Record a request on behalf of anyone who needs help, member or not.</p>
                </div>
                <Link :href="route('admin.help-requests.index')" class="text-sm font-bold text-gray-600 hover:text-gray-900">← Back to Help Requests</Link>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white shadow-xs sm:rounded-xl border border-gray-100 p-6 sm:p-8 space-y-8">
                    <section>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-4">Person needing help</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                                <input v-model="form.name" type="text" required :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.name" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mobile Number <span class="text-red-500">*</span></label>
                                <input v-model="form.mobile" type="tel" required :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.mobile" />
                            </div>
                            <div>
                                <label class="flex items-center justify-between text-sm font-medium text-gray-700">
                                    <span>WhatsApp Number</span>
                                    <button type="button" @click="sameAsMobile" class="text-xs font-bold text-green-700 hover:underline">Same as mobile</button>
                                </label>
                                <input v-model="form.whatsapp" type="tel" :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.whatsapp" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">State <span class="text-red-500">*</span></label>
                                <input v-model="form.state" type="text" required :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.state" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">District <span class="text-red-500">*</span></label>
                                <input v-model="form.district" type="text" required :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.district" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">City / Village <span class="text-red-500">*</span></label>
                                <input v-model="form.city" type="text" required :class="inputClass">
                                <InputError class="mt-1" :message="form.errors.city" />
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-4">Request</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                <select v-model="form.category" required :class="inputClass">
                                    <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.category" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Urgency <span class="text-red-500">*</span></label>
                                <select v-model="form.urgency" required :class="inputClass">
                                    <option v-for="urgency in urgencies" :key="urgency" :value="urgency">{{ urgency }}</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.urgency" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Title <span class="text-red-500">*</span></label>
                                <input v-model="form.title" type="text" required placeholder="Short summary, e.g. Financial help for cancer treatment" :class="inputClass">
                                <p class="mt-1 text-xs text-gray-500">Used in the social media preview card; an English or Hinglish title shows on the card image.</p>
                                <InputError class="mt-1" :message="form.errors.title" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Detailed Description <span class="text-red-500">*</span></label>
                                <textarea v-model="form.description" rows="6" required placeholder="What help is needed, for whom, and how people can help" :class="inputClass"></textarea>
                                <InputError class="mt-1" :message="form.errors.description" />
                            </div>
                        </div>
                    </section>

                    <section>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-4">Publishing</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select v-model="form.status" :class="inputClass">
                                    <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                                </select>
                                <InputError class="mt-1" :message="form.errors.status" />
                            </div>
                            <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 sm:mt-6">
                                <input v-model="form.is_public" type="checkbox" class="mt-0.5 h-4 w-4 rounded-sm border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="text-sm">
                                    <span class="font-medium text-gray-800">Show publicly on the Help page</span>
                                    <span class="block text-xs text-gray-500">Public requests with status Approved, Assigned, In Progress or Resolved are listed and shareable.</span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                        <Link :href="route('admin.help-requests.index')" class="px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-orange-600 text-white font-bold rounded-lg shadow-xs hover:bg-orange-700 transition-colors disabled:cursor-not-allowed disabled:opacity-60">
                            {{ form.processing ? 'Creating…' : 'Create Help Request' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
