<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import SeoMeta from '@/Components/SeoMeta.vue';
import HomeHero from '@/Components/Home/HomeHero.vue';
import TrustStats from '@/Components/Home/TrustStats.vue';
import AboutCommunity from '@/Components/Home/AboutCommunity.vue';
import KushwahaPrideSection from '@/Components/Home/KushwahaPrideSection.vue';
import PublicRepresentativesSection from '@/Components/Home/PublicRepresentativesSection.vue';
import WhyJoin from '@/Components/Home/WhyJoin.vue';
import JoinCommunityCta from '@/Components/Home/JoinCommunityCta.vue';
import CommunityDirectoryPreview from '@/Components/Home/CommunityDirectoryPreview.vue';
import MatrimonialSection from '@/Components/Home/MatrimonialSection.vue';
import HelpEachOtherSection from '@/Components/Home/HelpEachOtherSection.vue';
import FounderMessage from '@/Components/Home/FounderMessage.vue';
import SocialConnectSection from '@/Components/Home/SocialConnectSection.vue';
import UpcomingEvents from '@/Components/Home/UpcomingEvents.vue';
import LatestNewsStories from '@/Components/Home/LatestNewsStories.vue';
import FinalCta from '@/Components/Home/FinalCta.vue';

defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    members: {
        type: Array,
        default: () => [],
    },
    upcomingEvents: {
        type: Array,
        default: () => [],
    },
    updates: {
        type: Object,
        default: () => ({}),
    },
    prideHighlight: {
        type: Object,
        default: () => ({ featured: null, cards: [] }),
    },
    representativeHighlights: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const settings = computed(() => page.props.websiteSettings || {});

const socialHref = computed(() => {
    if (settings.value.whatsapp_group_link) {
        return settings.value.whatsapp_group_link;
    }

    return '#social-connect';
});
</script>

<template>
    <SeoMeta
        title="KushwahaConnect - Digital Community Platform"
        description="KushwahaConnect.com connects, supports, and empowers Kushwaha Samaj through community profiles, help, business networking, events, news, and matrimonial services."
    />

    <PublicLayout>
        <HomeHero :settings="settings" :social-href="socialHref" class="kc-fade-up" />
        <TrustStats :stats="stats" class="kc-fade-up" />
        <AboutCommunity :settings="settings" class="kc-fade-up" />
        <KushwahaPrideSection :highlight="prideHighlight" class="kc-fade-up" />
        <PublicRepresentativesSection :representatives="representativeHighlights" class="kc-fade-up" />
        <WhyJoin class="kc-fade-up" />
        <JoinCommunityCta class="kc-fade-up" />
        <CommunityDirectoryPreview :members="members" class="kc-fade-up" />
        <MatrimonialSection class="kc-fade-up" />
        <HelpEachOtherSection class="kc-fade-up" />
        <FounderMessage :settings="settings" class="kc-fade-up" />
        <SocialConnectSection :settings="settings" class="kc-fade-up" />
        <UpcomingEvents :events="upcomingEvents" class="kc-fade-up" />
        <LatestNewsStories :updates="updates" class="kc-fade-up" />
        <FinalCta :settings="settings" class="kc-fade-up" />
    </PublicLayout>
</template>
