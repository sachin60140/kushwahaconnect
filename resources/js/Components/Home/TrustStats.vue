<script setup>
import { computed, onMounted, ref } from 'vue';
import { useLanguage } from '@/composables/useLanguage';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const { __ } = useLanguage();
const progress = ref(0);

const cards = computed(() => [
    { key: 'members', label: __('stat_members'), value: Number(props.stats.members || 0), tone: 'text-orange-600' },
    { key: 'districts', label: __('stat_districts'), value: Number(props.stats.districts || 0), tone: 'text-blue-950' },
    { key: 'volunteers', label: __('stat_volunteers'), value: Number(props.stats.volunteers || 0), tone: 'text-green-700' },
    { key: 'matrimonial', label: __('stat_matrimonial'), value: Number(props.stats.matrimonial || 0), tone: 'text-orange-600' },
]);

const displayedValue = (value) => {
    const current = Math.round(value * progress.value);
    return new Intl.NumberFormat().format(current);
};

onMounted(() => {
    const start = performance.now();
    const duration = 900;

    const tick = (time) => {
        progress.value = Math.min((time - start) / duration, 1);
        if (progress.value < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
});
</script>

<template>
    <section class="-mt-8 relative z-10 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-3xl border border-slate-100 bg-white p-4 shadow-xl shadow-blue-950/10">
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div
                    v-for="card in cards"
                    :key="card.key"
                    class="rounded-2xl bg-[#fffaf0] p-5 text-center transition hover:-translate-y-1 hover:shadow-md"
                >
                    <p class="text-3xl font-black sm:text-4xl" :class="card.tone">
                        {{ displayedValue(card.value) }}<span v-if="card.value > 0">+</span>
                    </p>
                    <p class="mt-2 text-sm font-bold text-slate-600">{{ card.label }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
