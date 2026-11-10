<script setup>
// Opens WhatsApp (app or web) with a prefilled welcome message for one person.
defineProps({
    href: {
        type: String,
        default: null,
    },
    // 'link' for table rows, 'button' for modal footers.
    variant: {
        type: String,
        default: 'link',
    },
    disabledReason: {
        type: String,
        default: '',
    },
    // Optional text / tooltip for other prefilled messages (e.g. a verify reminder).
    label: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <a
        v-if="href && !disabledReason"
        :href="href"
        target="_blank"
        rel="noopener noreferrer"
        :title="title || 'Send a WhatsApp welcome message'"
        :class="variant === 'button'
            ? 'inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-xs hover:bg-green-700 transition-colors'
            : 'inline-flex items-center gap-1 text-green-700 hover:text-green-900 font-bold transition-colors'"
    >
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7a2.8 2.8 0 0 0 1.8-1.3 2.3 2.3 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z" />
        </svg>
        <span>{{ label || (variant === 'button' ? 'WhatsApp Welcome' : 'WhatsApp') }}</span>
    </a>
    <span
        v-else
        class="inline-flex items-center gap-1 text-gray-400 font-bold cursor-not-allowed"
        :title="disabledReason || 'No valid mobile number'"
    >
        <span>WhatsApp</span>
    </span>
</template>
