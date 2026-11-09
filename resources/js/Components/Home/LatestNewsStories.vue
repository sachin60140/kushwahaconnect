<script setup>
import { computed, ref } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    updates: {
        type: Object,
        default: () => ({}),
    },
});

const { __ } = useLanguage();
const activeTab = ref('news');

const tabs = [
    { key: 'news', label: 'tab_news' },
    { key: 'success_story', label: 'tab_success' },
    { key: 'update', label: 'tab_updates' },
];

const activeItems = computed(() => props.updates[activeTab.value] || []);
</script>

<template>
    <section class="bg-[#fffaf0] px-4 py-16 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-black tracking-tight text-blue-950 sm:text-4xl">{{ __('updates_title') }}</h2>
                <p class="mt-4 text-lg leading-8 text-slate-600">{{ __('updates_desc') }}</p>
            </div>

            <div class="mt-9 flex justify-center">
                <div class="grid w-full gap-2 rounded-2xl bg-white p-2 shadow-xs sm:w-auto sm:grid-cols-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="min-h-12 rounded-xl px-5 text-sm font-black transition"
                        :class="activeTab === tab.key ? 'bg-blue-950 text-white' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-950'"
                        @click="activeTab = tab.key"
                    >
                        {{ __(tab.label) }}
                    </button>
                </div>
            </div>

            <div v-if="activeItems.length" class="mt-9 grid gap-5 md:grid-cols-3">
                <article
                    v-for="item in activeItems"
                    :key="item.id"
                    class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xs transition hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-950/10"
                >
                    <div class="h-40 bg-linear-to-br from-blue-950 to-orange-500">
                        <img v-if="item.image_url" :src="item.image_url" :alt="item.title" class="h-full w-full object-cover" loading="lazy">
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-black uppercase tracking-[0.16em] text-orange-600">{{ __(tabs.find((tab) => tab.key === activeTab)?.label || 'tab_news') }}</p>
                        <h3 class="mt-3 line-clamp-2 text-xl font-black text-blue-950">{{ item.title }}</h3>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{{ item.content }}</p>
                        <a href="/news" class="mt-5 inline-flex min-h-11 items-center justify-center rounded-full bg-blue-950 px-5 text-sm font-black text-white transition hover:bg-blue-900">
                            {{ __('btn_read_more') }}
                        </a>
                    </div>
                </article>
            </div>

            <div v-else class="mt-9 rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <p class="text-base font-bold text-slate-600">{{ __('updates_empty') }}</p>
            </div>
        </div>
    </section>
</template>
