<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: 'KushwahaConnect - The premier digital platform connecting the Kushwaha community worldwide. Find jobs, matrimonial profiles, education resources, and connect with members.',
    },
    keywords: {
        type: String,
        default: 'kushwaha, community, connect, matrimonial, directory, network, jobs, education, resources',
    },
    image: {
        type: String,
        default: '/images/kushwahaconnect-social-preview.png',
    },
    url: {
        type: String,
        default: '',
    },
});

const page = usePage();

const displayTitle = computed(() => {
    return props.title.toLowerCase().includes('kushwahaconnect')
        ? props.title
        : `${props.title} - KushwahaConnect`;
});

const absoluteImage = computed(() => {
    if (props.image.startsWith('http://') || props.image.startsWith('https://')) {
        return props.image;
    }

    return `${window.location.origin}${props.image.startsWith('/') ? '' : '/'}${props.image}`;
});

const currentUrl = computed(() => props.url || page.props?.seo?.url || window.location.href);
</script>

<template>
    <Head :title="title">
        <meta head-key="description" name="description" :content="description" />
        <meta head-key="keywords" name="keywords" :content="keywords" />
        
        <!-- Open Graph / Facebook -->
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="og:title" property="og:title" :content="displayTitle" />
        <meta head-key="og:description" property="og:description" :content="description" />
        <meta head-key="og:url" property="og:url" :content="currentUrl" />
        <meta head-key="og:image" property="og:image" :content="absoluteImage" />
        <meta head-key="og:image:width" property="og:image:width" content="1200" />
        <meta head-key="og:image:height" property="og:image:height" content="630" />
        
        <!-- Twitter -->
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <meta head-key="twitter:title" name="twitter:title" :content="displayTitle" />
        <meta head-key="twitter:description" name="twitter:description" :content="description" />
        <meta head-key="twitter:image" name="twitter:image" :content="absoluteImage" />
    </Head>
</template>
