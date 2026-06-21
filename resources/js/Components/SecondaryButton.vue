<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-12 items-center justify-center rounded-full border border-blue-950/15 bg-white px-6 text-sm font-black text-blue-950 shadow-sm transition hover:border-blue-950 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 disabled:opacity-60"
    >
        <slot />
    </button>
</template>
