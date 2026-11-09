<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'block w-full border-l-4 border-orange-500 bg-orange-50 py-3 pe-4 ps-4 text-start text-base font-black text-blue-950 transition duration-150 ease-in-out focus:outline-hidden'
        : 'block w-full border-l-4 border-transparent py-3 pe-4 ps-4 text-start text-base font-bold text-slate-600 transition duration-150 ease-in-out hover:border-orange-200 hover:bg-orange-50 hover:text-blue-950 focus:outline-hidden',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
