<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

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
            <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-1">Medical / supporting documents &amp; photos</h3>
            <p class="text-xs text-gray-500 mb-3">
                Optional. Up to {{ maxDocuments }} files (PDF, JPG, PNG, WebP; max 5 MB each), e.g. medical reports, hospital bills, estimates or photos.
                Shown on the public request page once it is approved.
            </p>

            <ul v-if="existingDocuments.length" class="mb-3 divide-y divide-gray-100 rounded-lg border border-gray-200">
                <li v-for="doc in existingDocuments" :key="doc.id" class="flex items-center justify-between gap-3 px-3 py-2 text-sm" :class="{ 'opacity-50 line-through': (form.remove_documents || []).includes(doc.id) }">
                    <a :href="doc.url" target="_blank" rel="noopener" class="min-w-0 truncate font-medium text-indigo-700 hover:underline">
                        {{ doc.is_image ? '🖼️' : '📄' }} {{ doc.original_name }}
                    </a>
                    <label class="flex shrink-0 items-center gap-1 text-xs font-bold text-red-600">
                        <input type="checkbox" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500" :checked="(form.remove_documents || []).includes(doc.id)" @change="toggleExisting(doc.id)">
                        Remove
                    </label>
                </li>
            </ul>

            <ul v-if="(form.documents || []).length" class="mb-3 divide-y divide-gray-100 rounded-lg border border-green-200 bg-green-50">
                <li v-for="(file, index) in form.documents" :key="`${file.name}-${index}`" class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                    <span class="min-w-0 truncate">{{ file.type?.startsWith('image/') ? '🖼️' : '📄' }} {{ file.name }} <span class="text-xs text-gray-500">({{ formatSize(file.size) }})</span></span>
                    <button type="button" class="shrink-0 text-xs font-bold text-red-600 hover:underline" @click="removeNewDocument(index)">Remove</button>
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
                    class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-2 text-sm font-bold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                    @click="fileInput?.click()"
                >
                    + Add documents / photos
                </button>
                <span class="text-xs text-gray-500">{{ total }} of {{ maxDocuments }} attached</span>
            </div>
            <p v-for="(message, index) in documentErrors" :key="index" class="mt-1 text-sm text-red-600">{{ message }}</p>
        </section>

        <!-- Donation details -->
        <section>
            <h3 class="text-xs font-bold uppercase tracking-widest text-orange-600 mb-1">Bank account &amp; UPI for donations</h3>
            <p class="text-xs text-gray-500 mb-3">
                Optional. If you add a bank account, the account holder name, account number and IFSC are all needed.
                Shown publicly only after the request is approved, with a note asking donors to verify before paying.
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Account Holder Name</label>
                    <input v-model="form.account_holder_name" type="text" autocomplete="off" :class="inputClass">
                    <p v-if="form.errors.account_holder_name" class="mt-1 text-sm text-red-600">{{ form.errors.account_holder_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Account Number</label>
                    <input v-model="form.bank_account_number" type="text" inputmode="numeric" autocomplete="off" :class="inputClass">
                    <p v-if="form.errors.bank_account_number" class="mt-1 text-sm text-red-600">{{ form.errors.bank_account_number }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">IFSC Code</label>
                    <input v-model="form.bank_ifsc" type="text" maxlength="11" autocomplete="off" placeholder="e.g. SBIN0001234" :class="[inputClass, 'uppercase']">
                    <p v-if="form.errors.bank_ifsc" class="mt-1 text-sm text-red-600">{{ form.errors.bank_ifsc }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                    <input v-model="form.bank_name" type="text" :class="inputClass">
                    <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">{{ form.errors.bank_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Branch</label>
                    <input v-model="form.bank_branch" type="text" :class="inputClass">
                    <p v-if="form.errors.bank_branch" class="mt-1 text-sm text-red-600">{{ form.errors.bank_branch }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">UPI ID</label>
                    <input v-model="form.upi_id" type="text" autocomplete="off" placeholder="e.g. name@okicici" :class="inputClass">
                    <p v-if="form.errors.upi_id" class="mt-1 text-sm text-red-600">{{ form.errors.upi_id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">UPI QR Code (image, max 2 MB)</label>
                    <input ref="qrInput" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:font-semibold file:text-gray-800 hover:file:bg-gray-200" @change="pickQr">
                    <p v-if="form.errors.upi_qr" class="mt-1 text-sm text-red-600">{{ form.errors.upi_qr }}</p>
                </div>
                <div v-if="qrPreview || (existingUpiQrUrl && !form.remove_upi_qr)" class="sm:col-span-2 flex items-center gap-4">
                    <img :src="qrPreview || existingUpiQrUrl" alt="UPI QR code" class="h-32 w-32 rounded-lg border border-gray-200 bg-white object-contain p-1">
                    <button v-if="qrPreview" type="button" class="text-xs font-bold text-red-600 hover:underline" @click="clearQr">Don't upload this QR</button>
                    <label v-else class="flex items-center gap-1 text-xs font-bold text-red-600">
                        <input v-model="form.remove_upi_qr" type="checkbox" class="rounded-sm border-gray-300 text-red-600 focus:ring-red-500">
                        Remove current QR
                    </label>
                </div>
                <p v-else-if="existingUpiQrUrl && form.remove_upi_qr" class="sm:col-span-2 text-xs font-bold text-red-600">
                    Current QR will be removed on save.
                    <button type="button" class="ml-2 text-gray-600 underline" @click="form.remove_upi_qr = false">Undo</button>
                </p>
            </div>
        </section>
    </div>
</template>
