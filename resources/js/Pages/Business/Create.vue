<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';
import axios from 'axios';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useLanguage } from '@/composables/useLanguage';
import { BUSINESS_CATEGORIES, optionLabel, placeName } from '@/utils/optionLabels';

const props = defineProps({
    states: Array,
    categories: Array
});

const { __, locale } = useLanguage();

const districts = ref([]);

const form = useForm({
    business_name: '',
    category: '',
    description: '',
    address: '',
    city: '',
    state_id: '',
    district_id: '',
    pincode: '',
    contact_number: '',
    email: '',
    website: '',
    logo: null,
    cover_image: null
});

const fetchDistricts = async () => {
    if (form.state_id) {
        const response = await axios.get(`/api/states/${form.state_id}/districts`);
        districts.value = response.data;
    } else {
        districts.value = [];
        form.district_id = '';
    }
};

watch(() => form.state_id, () => {
    form.district_id = '';
    fetchDistricts();
});

const submit = () => {
    form.post(route('businesses.store'), {
        preserveScroll: true
    });
};

const required = (key) => `${__(key)} *`;
</script>

<template>
    <Head title="List Your Business" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('biz_list_your_business') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg border border-gray-200">
                    <div class="p-8 text-gray-900">
                        <div class="mb-8">
                            <h3 class="text-2xl font-bold text-gray-900">{{ __('biz_details') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('biz_details_intro') }}</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">

                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <InputLabel for="business_name" :value="required('biz_name')" />
                                    <TextInput id="business_name" type="text" class="mt-1 block w-full" v-model="form.business_name" required />
                                    <div v-if="form.errors.business_name" class="mt-1 text-sm text-red-600">{{ __(form.errors.business_name) }}</div>
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="category" :value="required('biz_category')" />
                                    <select id="category" v-model="form.category" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs" required>
                                        <option value="" disabled>{{ __('biz_select_category') }}</option>
                                        <option v-for="cat in categories" :key="cat" :value="cat">{{ optionLabel(__, BUSINESS_CATEGORIES, cat) }}</option>
                                    </select>
                                    <div v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ __(form.errors.category) }}</div>
                                </div>

                                <div class="md:col-span-2">
                                    <InputLabel for="description" :value="required('biz_description')" />
                                    <textarea id="description" v-model="form.description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs" required :placeholder="__('biz_description_placeholder')"></textarea>
                                    <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ __(form.errors.description) }}</div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('common_location') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="md:col-span-2">
                                        <InputLabel for="address" :value="required('biz_address')" />
                                        <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" required />
                                        <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ __(form.errors.address) }}</div>
                                    </div>

                                    <div>
                                        <InputLabel for="state_id" :value="required('join_state')" />
                                        <select id="state_id" v-model="form.state_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs" required>
                                            <option value="" disabled>{{ __('dir_select_state') }}</option>
                                            <option v-for="state in states" :key="state.id" :value="state.id">{{ placeName(locale, state) }}</option>
                                        </select>
                                        <div v-if="form.errors.state_id" class="mt-1 text-sm text-red-600">{{ __(form.errors.state_id) }}</div>
                                    </div>

                                    <div>
                                        <InputLabel for="district_id" :value="required('join_district')" />
                                        <select id="district_id" v-model="form.district_id" :disabled="!form.state_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs disabled:opacity-50" required>
                                            <option value="" disabled>{{ __('dir_select_district') }}</option>
                                            <option v-for="district in districts" :key="district.id" :value="district.id">{{ placeName(locale, district) }}</option>
                                        </select>
                                        <div v-if="form.errors.district_id" class="mt-1 text-sm text-red-600">{{ __(form.errors.district_id) }}</div>
                                    </div>

                                    <div>
                                        <InputLabel for="city" :value="required('biz_city')" />
                                        <TextInput id="city" type="text" class="mt-1 block w-full" v-model="form.city" required />
                                        <div v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ __(form.errors.city) }}</div>
                                    </div>

                                    <div>
                                        <InputLabel for="pincode" :value="required('biz_pincode')" />
                                        <TextInput id="pincode" type="text" inputmode="numeric" class="mt-1 block w-full" v-model="form.pincode" required />
                                        <div v-if="form.errors.pincode" class="mt-1 text-sm text-red-600">{{ __(form.errors.pincode) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-gray-200 pt-6">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">{{ __('biz_contact_info') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="contact_number" :value="required('biz_contact_number')" />
                                        <TextInput id="contact_number" type="text" class="mt-1 block w-full" v-model="form.contact_number" required />
                                        <div v-if="form.errors.contact_number" class="mt-1 text-sm text-red-600">{{ __(form.errors.contact_number) }}</div>
                                    </div>

                                    <div>
                                        <InputLabel for="email" :value="__('biz_email')" />
                                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                                        <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ __(form.errors.email) }}</div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="website" :value="__('biz_website')" />
                                        <TextInput id="website" type="url" class="mt-1 block w-full" v-model="form.website" placeholder="https://" />
                                        <div v-if="form.errors.website" class="mt-1 text-sm text-red-600">{{ __(form.errors.website) }}</div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="logo" :value="__('biz_logo')" />
                                        <input id="logo" type="file" @input="form.logo = $event.target.files[0]" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:min-h-11 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*" />
                                        <div v-if="form.errors.logo" class="mt-1 text-sm text-red-600">{{ __(form.errors.logo) }}</div>
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="cover_image" :value="__('biz_cover')" />
                                        <p class="text-xs text-gray-500 mb-2">{{ __('biz_cover_hint') }}</p>
                                        <input id="cover_image" type="file" @input="form.cover_image = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:min-h-11 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100" accept="image/*" />
                                        <div v-if="form.errors.cover_image" class="mt-1 text-sm text-red-600">{{ __(form.errors.cover_image) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-6 border-t border-gray-200">
                                <Link :href="route('businesses.index')" class="inline-flex min-h-11 items-center px-2 text-sm text-gray-600 hover:text-gray-900 mr-4">{{ __('common_cancel') }}</Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    {{ __('biz_submit') }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
