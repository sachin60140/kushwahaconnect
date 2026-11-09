<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    representative: Object,
    categories: Array,
    statuses: Array,
    states: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    _method: props.representative ? 'PUT' : 'POST',
    name: props.representative?.name || '',
    photo: null,
    representative_status: props.representative?.representative_status || 'current',
    category: props.representative?.category || '',
    current_designation: props.representative?.current_designation || '',
    former_designations: props.representative?.former_designations || '',
    political_party: props.representative?.political_party || '',
    constituency: props.representative?.constituency || '',
    state: props.representative?.state || '',
    district: props.representative?.district || '',
    house_or_body: props.representative?.house_or_body || '',
    term_start_date: props.representative?.term_start_date ? props.representative.term_start_date.slice(0, 10) : '',
    term_end_date: props.representative?.term_end_date ? props.representative.term_end_date.slice(0, 10) : '',
    biography_hi: props.representative?.biography_hi || '',
    biography_en: props.representative?.biography_en || '',
    official_website: props.representative?.official_website || '',
    facebook_url: props.representative?.facebook_url || '',
    instagram_url: props.representative?.instagram_url || '',
    linkedin_url: props.representative?.linkedin_url || '',
    youtube_url: props.representative?.youtube_url || '',
    x_url: props.representative?.x_url || '',
    government_source_url: props.representative?.government_source_url || '',
    election_source_url: props.representative?.election_source_url || '',
    last_verified_at: props.representative?.last_verified_at ? props.representative.last_verified_at.slice(0, 10) : '',
    verification_note: props.representative?.verification_note || '',
    is_verified: props.representative?.is_verified || false,
    is_featured: props.representative?.is_featured || false,
    display_order: props.representative?.display_order || 0,
    is_published: props.representative?.is_published || false,
});

const submit = () => {
    const routeName = props.representative
        ? route('admin.representatives.update', props.representative.id)
        : route('admin.representatives.store');

    form.post(routeName, { forceFormData: true });
};

const selectedState = computed(() => props.states.find((state) => state.name_en === form.state || state.name_hi === form.state));
const districtOptions = computed(() => selectedState.value?.districts || []);
const hasSelectedStateInList = computed(() => props.states.some((state) => state.name_en === form.state || state.name_hi === form.state));
const hasSelectedDistrictInList = computed(() => districtOptions.value.some((district) => district.name_en === form.district || district.name_hi === form.district));

watch(() => form.state, () => {
    if (! districtOptions.value.some((district) => district.name_en === form.district || district.name_hi === form.district)) {
        form.district = '';
    }
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-black text-blue-950">{{ representative ? 'Edit Public Representative' : 'Add Public Representative' }}</h2>
        </template>

        <div class="p-4 sm:p-6 lg:p-8">
            <form @submit.prevent="submit" class="kc-surface mx-auto max-w-5xl space-y-8 p-6">
                <div v-if="Object.keys(form.errors).length" class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-black">Please fix the following and save again.</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                    </ul>
                </div>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Identity and Role</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <input v-model="form.name" type="text" placeholder="Name" required>
                        <input type="file" accept="image/*" @input="form.photo = $event.target.files[0]">
                        <select v-model="form.representative_status" required>
                            <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
                        </select>
                        <select v-model="form.category" required>
                            <option value="">Select representative category</option>
                            <option v-for="category in categories" :key="category" :value="category">{{ category }}</option>
                        </select>
                        <input v-model="form.current_designation" type="text" placeholder="Current designation">
                        <textarea v-model="form.former_designations" rows="3" placeholder="Former designations"></textarea>
                    </div>
                    <img v-if="representative?.photo_url" :src="representative.photo_url" alt="Current representative photo" class="mt-4 h-24 w-24 rounded-2xl object-cover">
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Public Office Details</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-3">
                        <input v-model="form.political_party" type="text" placeholder="Political party">
                        <input v-model="form.constituency" type="text" placeholder="Constituency">
                        <input v-model="form.house_or_body" type="text" placeholder="House or public body">
                        <select v-model="form.state">
                            <option value="">Select state</option>
                            <option
                                v-if="form.state && !hasSelectedStateInList"
                                :value="form.state"
                            >
                                {{ form.state }}
                            </option>
                            <option v-for="state in states" :key="state.id" :value="state.name_en">
                                {{ state.name_en }}{{ state.name_hi && state.name_hi !== state.name_en ? ` / ${state.name_hi}` : '' }}
                            </option>
                        </select>
                        <select v-model="form.district" :disabled="!form.state">
                            <option value="">{{ form.state ? 'Select district' : 'Select state first' }}</option>
                            <option
                                v-if="form.district && !hasSelectedDistrictInList"
                                :value="form.district"
                            >
                                {{ form.district }}
                            </option>
                            <option v-for="district in districtOptions" :key="district.id" :value="district.name_en">
                                {{ district.name_en }}{{ district.name_hi && district.name_hi !== district.name_en ? ` / ${district.name_hi}` : '' }}
                            </option>
                        </select>
                        <input v-model="form.term_start_date" type="date" placeholder="Term start date">
                        <input v-model="form.term_end_date" type="date" placeholder="Term end date">
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Bilingual Biography</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <textarea v-model="form.biography_hi" rows="6" placeholder="Hindi biography"></textarea>
                        <textarea v-model="form.biography_en" rows="6" placeholder="English biography"></textarea>
                    </div>
                </section>

                <section>
                    <h3 class="text-lg font-black text-blue-950">Official Links and Verification</h3>
                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <input v-model="form.official_website" type="url" placeholder="Official website">
                        <input v-model="form.facebook_url" type="url" placeholder="Facebook URL">
                        <input v-model="form.instagram_url" type="url" placeholder="Instagram URL">
                        <input v-model="form.linkedin_url" type="url" placeholder="LinkedIn URL">
                        <input v-model="form.youtube_url" type="url" placeholder="YouTube URL">
                        <input v-model="form.x_url" type="url" placeholder="X/Twitter URL">
                        <input v-model="form.government_source_url" type="url" placeholder="Government source URL">
                        <input v-model="form.election_source_url" type="url" placeholder="Election source URL">
                        <input v-model="form.last_verified_at" type="date" placeholder="Last verified date">
                        <textarea v-model="form.verification_note" rows="4" placeholder="Verification note" class="md:col-span-2"></textarea>
                    </div>
                </section>

                <section class="grid gap-4 md:grid-cols-4">
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_verified" type="checkbox"> Verified</label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_featured" type="checkbox"> Homepage featured</label>
                    <label class="flex items-center gap-2 text-sm font-bold text-slate-700"><input v-model="form.is_published" type="checkbox"> Published</label>
                    <input v-model="form.display_order" type="number" min="0" placeholder="Display order">
                </section>

                <div class="rounded-2xl bg-green-50 p-4 text-sm font-bold text-green-900">
                    Political neutrality rule: do not rank politicians, do not publish unverified allegations, and clearly distinguish current and former roles.
                </div>

                <div class="flex justify-end gap-3">
                    <Link :href="route('admin.representatives.index')" class="kc-btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="kc-btn-primary">Save Representative</button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
