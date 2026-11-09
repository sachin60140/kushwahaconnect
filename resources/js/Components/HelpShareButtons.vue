<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Share one help request on social media. Links point at the request's own page
// (help.show), which carries its title/description in the social preview tags.
const props = defineProps({
    helpRequest: {
        type: Object,
        required: true,
    },
    // Icon-only row for cards; labelled buttons for the request page.
    compact: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const isHindi = computed(() => (page.props.locale || 'hi') === 'hi');
const copied = ref(false);
const canNativeShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

const url = computed(() => route('help.show', props.helpRequest.id));

const urgencyLabel = computed(() => {
    const labels = isHindi.value
        ? { Emergency: 'आपातकालीन', Urgent: 'तत्काल', Normal: 'सामान्य' }
        : { Emergency: 'Emergency', Urgent: 'Urgent', Normal: 'Normal' };

    return labels[props.helpRequest.urgency] || props.helpRequest.urgency || '';
});

const location = computed(() =>
    [props.helpRequest.city, props.helpRequest.state].filter(Boolean).join(', '),
);

const excerpt = computed(() => {
    const text = String(props.helpRequest.description || '').replace(/\s+/g, ' ').trim();
    return text.length > 220 ? `${text.slice(0, 217)}…` : text;
});

// Full appeal (WhatsApp, Telegram, copy): headline, place, story, call to action.
const message = computed(() => {
    const req = props.helpRequest;
    const lines = isHindi.value
        ? [
            `🚨 मदद की अपील (${urgencyLabel.value}) — ${req.title}`,
            location.value ? `📍 ${location.value}` : null,
            '',
            excerpt.value,
            '',
            'कृपया मदद करें और इसे ज़्यादा से ज़्यादा लोगों तक शेयर करें 🙏',
        ]
        : [
            `🚨 Help needed (${urgencyLabel.value}) — ${req.title}`,
            location.value ? `📍 ${location.value}` : null,
            '',
            excerpt.value,
            '',
            'Please help and share this as widely as you can 🙏',
        ];

    return lines.filter((line) => line !== null).join('\n');
});

const fullText = computed(() =>
    `${message.value}\n${isHindi.value ? 'पूरी जानकारी और संपर्क' : 'Details & contact'}: ${url.value}`,
);

const shortText = computed(() => {
    const req = props.helpRequest;
    const where = location.value ? ` — ${location.value}` : '';
    return isHindi.value
        ? `🚨 मदद की अपील: ${req.title}${where}. कृपया मदद करें और शेयर करें 🙏`
        : `🚨 Help needed: ${req.title}${where}. Please help & share 🙏`;
});

const enc = encodeURIComponent;

const targets = computed(() => [
    {
        key: 'whatsapp',
        label: 'WhatsApp',
        href: `https://wa.me/?text=${enc(fullText.value)}`,
        classes: 'bg-green-500 text-white hover:bg-green-600',
        path: 'M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm4.5 12.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z',
    },
    {
        key: 'facebook',
        label: 'Facebook',
        href: `https://www.facebook.com/sharer/sharer.php?u=${enc(url.value)}`,
        classes: 'bg-[#1877F2] text-white hover:bg-[#166fe0]',
        path: 'M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12Z',
    },
    {
        key: 'x',
        label: 'X',
        href: `https://twitter.com/intent/tweet?text=${enc(shortText.value)}&url=${enc(url.value)}`,
        classes: 'bg-black text-white hover:bg-gray-800',
        path: 'M18.2 2.3h3.4l-7.4 8.4 8.7 11.5h-6.8l-5.3-7-6.1 7H1.3l7.9-9L.9 2.3h7l4.8 6.4 5.5-6.4Zm-1.2 17.9h1.9L7.1 4.2H5.1l11.9 16Z',
    },
    {
        key: 'telegram',
        label: 'Telegram',
        href: `https://t.me/share/url?url=${enc(url.value)}&text=${enc(message.value)}`,
        classes: 'bg-[#229ED9] text-white hover:bg-[#1c8dc2]',
        path: 'M21.9 4.3 18.7 19.4c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.5 13.2l-4.7-1.5c-1-.3-1-1 .2-1.5L20.5 3c.9-.3 1.6.2 1.4 1.3Z',
    },
]);

const copyLink = async () => {
    try {
        await navigator.clipboard.writeText(fullText.value);
    } catch {
        window.prompt(isHindi.value ? 'लिंक कॉपी करें:' : 'Copy this link:', url.value);
        return;
    }

    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};

const nativeShare = async () => {
    try {
        await navigator.share({ title: props.helpRequest.title, text: message.value, url: url.value });
    } catch {
        // dismissed by the user
    }
};
</script>

<template>
    <div :class="compact ? 'flex items-center gap-2' : 'space-y-3'">
        <span v-if="compact" class="text-xs font-bold text-gray-500">{{ isHindi ? 'शेयर करें:' : 'Share:' }}</span>
        <p v-else class="text-sm font-black text-gray-900">{{ isHindi ? 'यह अपील शेयर करें' : 'Share this appeal' }}</p>

        <div class="flex flex-wrap items-center gap-2">
            <a
                v-for="target in targets"
                :key="target.key"
                :href="target.href"
                target="_blank"
                rel="noopener noreferrer"
                :title="`${isHindi ? 'शेयर करें' : 'Share on'} ${target.label}`"
                :aria-label="`${isHindi ? 'शेयर करें' : 'Share on'} ${target.label}`"
                class="inline-flex items-center justify-center gap-2 rounded-lg font-bold shadow-xs transition-colors"
                :class="[target.classes, compact ? 'h-8 w-8' : 'px-4 py-2 text-sm']"
            >
                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path :d="target.path" /></svg>
                <span v-if="!compact">{{ target.label }}</span>
            </a>

            <button
                type="button"
                @click="copyLink"
                :title="isHindi ? 'लिंक कॉपी करें' : 'Copy link'"
                :aria-label="isHindi ? 'लिंक कॉपी करें' : 'Copy link'"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white font-bold text-gray-700 shadow-xs transition-colors hover:bg-gray-50"
                :class="compact ? 'h-8 px-2 text-xs' : 'px-4 py-2 text-sm'"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.8 10.2a4 4 0 0 0-5.6 0l-4 4a4 4 0 1 0 5.6 5.6l1.1-1.1m-.7-4.9a4 4 0 0 0 5.6 0l4-4a4 4 0 1 0-5.6-5.6l-1.1 1.1" /></svg>
                <span v-if="copied">{{ isHindi ? 'कॉपी हुआ' : 'Copied' }}</span>
                <span v-else-if="!compact">{{ isHindi ? 'लिंक कॉपी करें' : 'Copy link' }}</span>
            </button>

            <button
                v-if="canNativeShare"
                type="button"
                @click="nativeShare"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white font-bold text-gray-700 shadow-xs transition-colors hover:bg-gray-50"
                :class="compact ? 'h-8 px-2 text-xs' : 'px-4 py-2 text-sm'"
            >
                {{ isHindi ? 'अन्य' : 'More' }}
            </button>
        </div>
    </div>
</template>
