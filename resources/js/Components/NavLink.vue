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
        ? 'inline-flex items-center border-b-2 border-orange-500 px-1 pt-1 text-sm font-black leading-5 text-blue-950 transition duration-150 ease-in-out focus:outline-hidden focus:border-orange-600'
        : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-bold leading-5 text-slate-500 transition duration-150 ease-in-out hover:border-orange-200 hover:text-blue-950 focus:outline-hidden focus:text-blue-950',
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
