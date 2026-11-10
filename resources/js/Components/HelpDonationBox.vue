<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Donation details for a public help request: bank account, UPI ID/QR, with a caution.
const props = defineProps({
    helpRequest: { type: Object, required: true },
});

const page = usePage();
const isHindi = computed(() => (page.props.locale || 'hi') === 'hi');
const copied = ref('');

const req = computed(() => props.helpRequest);
const hasBank = computed(() => !!req.value.bank_account_number);
const hasUpi = computed(() => !!(req.value.upi_id || req.value.upi_qr_url));

const bankRows = computed(() => [
    { key: 'holder', label: isHindi.value ? 'खाताधारक' : 'Account holder', value: req.value.account_holder_name },
    { key: 'number', label: isHindi.value ? 'खाता संख्या' : 'Account number', value: req.value.bank_account_number },
    { key: 'ifsc', label: 'IFSC', value: req.value.bank_ifsc },
    { key: 'bank', label: isHindi.value ? 'बैंक' : 'Bank', value: req.value.bank_name },
    { key: 'branch', label: isHindi.value ? 'शाखा' : 'Branch', value: req.value.bank_branch },
].filter((row) => row.value));

// Opens installed UPI apps on phones with the payee prefilled (amount left to the donor).
const upiLink = computed(() => {
    if (!req.value.upi_id) return null;
    const params = new URLSearchParams({
        pa: req.value.upi_id,
        pn: req.value.account_holder_name || req.value.name || 'KushwahaConnect help',
        tn: `Help: ${String(req.value.title || '').slice(0, 60)}`,
        cu: 'INR',
    });
    return `upi://pay?${params.toString()}`;
});

const copy = async (key, value) => {
    try {
        await navigator.clipboard.writeText(String(value));
        copied.value = key;
        setTimeout(() => {
            if (copied.value === key) copied.value = '';
        }, 1800);
    } catch {
        window.prompt(isHindi.value ? 'कॉपी करें:' : 'Copy:', String(value));
    }
};
</script>

<template>
    <section v-if="hasBank || hasUpi" class="rounded-xl border border-green-200 bg-green-50/60 p-5 sm:p-6">
        <h2 class="text-lg font-black text-green-900">{{ isHindi ? 'सीधे सहायता करें' : 'Support directly' }}</h2>
        <p class="mt-1 text-sm text-green-900/80">
            {{ isHindi ? 'आप नीचे दिए बैंक खाते या UPI से सीधे सहायता भेज सकते हैं।' : 'You can send help directly to the bank account or UPI below.' }}
        </p>

        <div class="mt-4 grid gap-5" :class="{ 'md:grid-cols-2': hasBank && hasUpi }">
            <div v-if="hasBank" class="rounded-lg border border-green-100 bg-white p-4">
                <h3 class="text-xs font-bold uppercase tracking-widest text-green-700 mb-3">{{ isHindi ? 'बैंक खाता' : 'Bank account' }}</h3>
                <dl class="space-y-2 text-sm">
                    <div v-for="row in bankRows" :key="row.key" class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ row.label }}</dt>
                        <dd class="flex min-w-0 items-center gap-2 font-bold text-gray-900">
                            <span class="truncate" :class="{ 'font-mono tracking-wide': row.key === 'number' || row.key === 'ifsc' }">{{ row.value }}</span>
                            <button
                                v-if="['holder', 'number', 'ifsc'].includes(row.key)"
                                type="button"
                                class="shrink-0 rounded-sm border border-gray-200 px-1.5 py-0.5 text-[11px] font-bold text-gray-600 hover:bg-gray-50"
                                @click="copy(row.key, row.value)"
                            >
                                {{ copied === row.key ? (isHindi ? 'कॉपी हुआ' : 'Copied') : (isHindi ? 'कॉपी' : 'Copy') }}
                            </button>
                        </dd>
                    </div>
                </dl>
            </div>

            <div v-if="hasUpi" class="rounded-lg border border-green-100 bg-white p-4 text-center">
                <h3 class="text-xs font-bold uppercase tracking-widest text-green-700 mb-3">UPI</h3>
                <a v-if="req.upi_qr_url" :href="req.upi_qr_url" target="_blank" rel="noopener" class="inline-block">
                    <img :src="req.upi_qr_url" :alt="isHindi ? 'UPI QR कोड' : 'UPI QR code'" class="mx-auto h-48 w-48 rounded-lg border border-gray-200 bg-white object-contain p-1">
                </a>
                <p v-if="req.upi_qr_url" class="mt-1 text-xs text-gray-500">{{ isHindi ? 'किसी भी UPI ऐप से स्कैन करें' : 'Scan with any UPI app' }}</p>
                <div v-if="req.upi_id" class="mt-3 flex items-center justify-center gap-2">
                    <span class="font-mono text-sm font-bold text-gray-900 break-all">{{ req.upi_id }}</span>
                    <button type="button" class="shrink-0 rounded-sm border border-gray-200 px-1.5 py-0.5 text-[11px] font-bold text-gray-600 hover:bg-gray-50" @click="copy('upi', req.upi_id)">
                        {{ copied === 'upi' ? (isHindi ? 'कॉपी हुआ' : 'Copied') : (isHindi ? 'कॉपी' : 'Copy') }}
                    </button>
                </div>
                <a v-if="upiLink" :href="upiLink" class="mt-3 inline-flex items-center justify-center rounded-lg bg-green-700 px-4 py-2 text-sm font-bold text-white hover:bg-green-800">
                    {{ isHindi ? 'UPI ऐप से भुगतान करें' : 'Pay with UPI app' }}
                </a>
            </div>
        </div>

        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-900">
            ⚠️ {{ isHindi
                ? 'भुगतान से पहले अनुरोधकर्ता से फ़ोन पर पुष्टि अवश्य करें। KushwahaConnect कोई पैसा नहीं लेता या रखता है और किसी भी लेन-देन के लिए ज़िम्मेदार नहीं है।'
                : 'Please confirm with the requester by phone before paying. KushwahaConnect does not collect or hold money and is not responsible for any transaction.' }}
        </p>
    </section>
</template>
