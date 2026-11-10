<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

// Supporting documents/photos + donation details (bank, UPI) for a help request.
// Shared by the public "Request Help" form and the admin create/edit form.
// The parent's useForm must contain: documents, account_holder_name, bank_account_number,
// bank_ifsc, bank_name, bank_branch, upi_id, upi_qr (+ remove_documents, remove_upi_qr for edits).
const props = defineProps({
    form: { type: Object, required: true },
    existingDocuments: { type: Array, default: () => [] },
    existingUpiQrUrl: { type: String, default: null },
    maxDocuments: { type: Number, default: 5 },
});

const { __ } = useLanguage();

const fileInput = ref(null);
const qrInput = ref(null);
const qrPreview = ref(null);

const kept = computed(() =>
    props.existingDocuments.filter((doc) => !(props.form.remove_documents || []).includes(doc.id)),
);
const total = computed(() => kept.value.length + (props.form.documents || []).length);
const slotsLeft = computed(() => Math.max(0, props.maxDocuments - total.value));

const addDocuments = (event) => {
    const picked = Array.from(event.target.files || []);
    props.form.documents = [...(props.form.documents || []), ...picked].slice(0, props.maxDocuments - kept.value.length);
    event.target.value = '';
};

const removeNewDocument = (index) => {
    props.form.documents = props.form.documents.filter((_, i) => i !== index);
};

const toggleExisting = (id) => {
    const list = props.form.remove_documents || [];
    props.form.remove_documents = list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
};

const pickQr = (event) => {
    const file = event.target.files?.[0] || null;
    props.form.upi_qr = file;
    if (qrPreview.value) URL.revokeObjectURL(qrPreview.value);
    qrPreview.value = file ? URL.createObjectURL(file) : null;
    if (file) props.form.remove_upi_qr = false;
};

const clearQr = () => {
    props.form.upi_qr = null;
    if (qrPreview.value) URL.revokeObjectURL(qrPreview.value);
    qrPreview.value = null;
    if (qrInput.value) qrInput.value.value = '';
};

// Reset the local preview when the parent resets the form after a successful submit.
watch(() => props.form.upi_qr, (value) => {
    if (!value && qrPreview.value) clearQr();
});

onBeforeUnmount(() => {
    if (qrPreview.value) URL.revokeObjectURL(qrPreview.value);
});

const documentErrors = computed(() =>
    Object.entries(props.form.errors || {})
        .filter(([key]) => key === 'documents' || key.startsWith('documents.'))
        .map(([, message]) => message),
);

const formatSize = (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);

// Literal class names so Tailwind's scanner picks them up.
const inputClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-xs sm:text-sm focus:border-orange-500 focus:ring-orange-500';
</script>

<template>
    <div class="space-y-8">
        <!-- Documents / photos -->
        <section>
            <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-1">{{ __('help_docs_title') }}</h3>
            <p class="text-xs text-gray-500 mb-3">
                {{ __('help_docs_hint').replace(':max', maxDocuments) }}
            </p>

            <ul v-if="existingDocuments.length" class="mb-3 divide-y divide-gray-100 rounded-lg border border-gray-200">
                <li v-for="doc in existingDocuments" :key="doc.id" class="flex items-center justify-between gap-3 px-3 py-2 text-sm" :class="{ 'opacity-50 line-through': (form.remove_documents || []).includes(doc.id) }">
                    <a :href="doc.url" target="_blank" rel="noopener" class="min-w-0 truncate font-medium text-indigo-700 hover:underline">
                        {{ doc.is_image ? '🖼️' : '📄' }} {{ doc.original_name }}
                    </a>
                    <label class="flex min-h-11 shrink-0 cursor-pointer items-center gap-2 text-xs font-bold text-red-600">
                        <input type="checkbox" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500" :checked="(form.remove_documents || []).includes(doc.id)" @change="toggleExisting(doc.id)">
                        {{ __('help_docs_remove') }}
                    </label>
                </li>
            </ul>

            <ul v-if="(form.documents || []).length" class="mb-3 divide-y divide-gray-100 rounded-lg border border-green-200 bg-green-50">
                <li v-for="(file, index) in form.documents" :key="`${file.name}-${index}`" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                    <span class="min-w-0 truncate">{{ file.type?.startsWith('image/') ? '🖼️' : '📄' }} {{ file.name }} <span class="text-xs text-gray-500">({{ formatSize(file.size) }})</span></span>
                    <button type="button" class="inline-flex min-h-11 shrink-0 items-center px-2 text-xs font-bold text-red-600 hover:underline" @click="removeNewDocument(index)">{{ __('help_docs_remove') }}</button>
                </li>
            </ul>

            <div class="flex flex-wrap items-center gap-3">
                <input
                    ref="fileInput"
                    type="file"
                    multiple
                    accept="application/pdf,image/jpeg,image/png,image/webp"
                    class="hidden"
                    @change="addDocuments"
                >
                <button
                    type="button"
                    :disabled="slotsLeft === 0"
                    class="min-h-11 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="fileInput?.click()"
                >
                    {{ __('help_docs_add') }}
                </button>
                <span class="text-xs text-gray-500">{{ __('help_docs_count').replace(':count', total).replace(':max', maxDocuments) }}</span>
            </div>
            <p v-for="(message, index) in documentErrors" :key="index" class="mt-1 text-sm text-red-600">{{ __(message) }}</p>
        </section>

        <!-- Donation details -->
        <section>
            <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-1">{{ __('help_bank_title') }}</h3>
            <p class="text-xs text-gray-500 mb-3">
                {{ __('help_bank_hint') }}
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_account_holder') }}</label>
                    <input v-model="form.account_holder_name" type="text" autocomplete="off" :class="inputClass">
                    <p v-if="form.errors.account_holder_name" class="mt-1 text-sm text-red-600">{{ __(form.errors.account_holder_name) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_account_number') }}</label>
                    <input v-model="form.bank_account_number" type="text" inputmode="numeric" autocomplete="off" :class="inputClass">
                    <p v-if="form.errors.bank_account_number" class="mt-1 text-sm text-red-600">{{ __(form.errors.bank_account_number) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_ifsc') }}</label>
                    <input v-model="form.bank_ifsc" type="text" maxlength="11" autocomplete="off" :placeholder="__('help_ifsc_placeholder')" :class="[inputClass, 'uppercase']">
                    <p v-if="form.errors.bank_ifsc" class="mt-1 text-sm text-red-600">{{ __(form.errors.bank_ifsc) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_bank_name') }}</label>
                    <input v-model="form.bank_name" type="text" :class="inputClass">
                    <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">{{ __(form.errors.bank_name) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_branch') }}</label>
                    <input v-model="form.bank_branch" type="text" :class="inputClass">
                    <p v-if="form.errors.bank_branch" class="mt-1 text-sm text-red-600">{{ __(form.errors.bank_branch) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_upi_id') }}</label>
                    <input v-model="form.upi_id" type="text" autocomplete="off" :placeholder="__('help_upi_id_placeholder')" :class="inputClass">
                    <p v-if="form.errors.upi_id" class="mt-1 text-sm text-red-600">{{ __(form.errors.upi_id) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('help_upi_qr') }}</label>
                    <input ref="qrInput" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block min-h-11 w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-semibold file:text-gray-800 hover:file:bg-gray-200" @change="pickQr">
                    <p v-if="form.errors.upi_qr" class="mt-1 text-sm text-red-600">{{ __(form.errors.upi_qr) }}</p>
                </div>
                <div v-if="qrPreview || (existingUpiQrUrl && !form.remove_upi_qr)" class="sm:col-span-2 flex items-center gap-4">
                    <img :src="qrPreview || existingUpiQrUrl" :alt="__('help_upi_qr_alt')" class="h-32 w-32 rounded-lg border border-gray-200 bg-white object-contain p-1">
                    <button v-if="qrPreview" type="button" class="inline-flex min-h-11 items-center text-xs font-bold text-red-600 hover:underline" @click="clearQr">{{ __('help_qr_dont_upload') }}</button>
                    <label v-else class="flex min-h-11 cursor-pointer items-center gap-2 text-xs font-bold text-red-600">
                        <input v-model="form.remove_upi_qr" type="checkbox" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500">
                        {{ __('help_qr_remove_current') }}
                    </label>
                </div>
                <p v-else-if="existingUpiQrUrl && form.remove_upi_qr" class="sm:col-span-2 text-xs font-bold text-red-600">
                    {{ __('help_qr_will_remove') }}
                    <button type="button" class="ml-2 inline-flex min-h-11 items-center text-gray-600 underline" @click="form.remove_upi_qr = false">{{ __('help_undo') }}</button>
                </p>
            </div>
        </section>
    </div>
</template>
