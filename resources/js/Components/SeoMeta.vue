<script setup>
import { Head } from '@inertiajs/vue3';

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
        default: '/images/og-image.jpg',
    },
});
</script>

<template>
    <Head :title="title">
        <meta name="description" :content="description" />
        <meta name="keywords" :content="keywords" />
        
        <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website" />
        <meta property="og:title" :content="`${title} - KushwahaConnect`" />
        <meta property="og:description" :content="description" />
        <meta property="og:image" :content="image" />
        
        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image" />
        <meta property="twitter:title" :content="`${title} - KushwahaConnect`" />
        <meta property="twitter:description" :content="description" />
        <meta property="twitter:image" :content="image" />
    </Head>
</template>
