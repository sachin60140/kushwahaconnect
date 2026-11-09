<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
});

const form = useForm({
    // The model has no boolean casts, so stored values arrive as 0/1; checkboxes need real booleans.
    public_profile_visible: Boolean(props.settings.public_profile_visible ?? true),
    mobile_visibility: props.settings.mobile_visibility || 'hidden',
    email_visibility: props.settings.email_visibility || 'hidden',
    photo_visibility: props.settings.photo_visibility || 'public',
    address_visibility: props.settings.address_visibility || 'city_only',
    matrimonial_show_first_name_only: Boolean(props.settings.matrimonial_show_first_name_only),
    matrimonial_photo_visibility: props.settings.matrimonial_photo_visibility || 'public',
    matrimonial_hide_income: Boolean(props.settings.matrimonial_hide_income),
});

const submit = () => {
    form.post(route('user.privacy-settings.update'));
};
</script>

<template>
    <Head title="Privacy Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Privacy Settings
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xs sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Manage Your Privacy</h3>
                            <p class="mt-1 text-sm text-gray-600">Control what information is visible to others in the Community and Matrimonial Directories.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-8 max-w-3xl">
                            
                            <!-- Community Directory Privacy -->
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-100">
                                <h4 class="font-semibold text-blue-800 mb-4 border-b border-blue-200 pb-2">Community Directory Visibility</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded-sm border-gray-300 text-blue-600 shadow-xs focus:ring-blue-500" v-model="form.public_profile_visible">
                                            <span class="ml-2 text-gray-800 font-medium">Make my Community Profile public</span>
                                        </label>
                                        <p class="text-sm text-gray-600 ml-6 mt-1">If unchecked, your profile will not appear in the community directory search at all.</p>
                                    </div>

                                    <div v-if="form.public_profile_visible" class="ml-6 space-y-4 mt-4 border-t border-blue-100 pt-4">
                                        <div>
                                            <InputLabel for="mobile_visibility" value="Mobile Number Visibility" />
                                            <select id="mobile_visibility" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-xs" v-model="form.mobile_visibility">
                                                <option value="hidden">Hidden from everyone</option>
                                                <option value="connections_only">Visible to Connections only</option>
                                                <option value="public">Visible to all registered members</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.mobile_visibility" />
                                        </div>

                                        <div>
                                            <InputLabel for="email_visibility" value="Email Visibility" />
                                            <select id="email_visibility" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-xs" v-model="form.email_visibility">
                                                <option value="hidden">Hidden from everyone</option>
                                                <option value="connections_only">Visible to Connections only</option>
                                                <option value="public">Visible to all registered members</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.email_visibility" />
                                        </div>

                                        <div>
                                            <InputLabel for="photo_visibility" value="Profile Photo Visibility" />
                                            <select id="photo_visibility" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-xs" v-model="form.photo_visibility">
                                                <option value="hidden">Hidden</option>
                                                <option value="connections_only">Connections only</option>
                                                <option value="public">Public</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.photo_visibility" />
                                        </div>

                                        <div>
                                            <InputLabel for="address_visibility" value="Address Visibility" />
                                            <select id="address_visibility" class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-xs" v-model="form.address_visibility">
                                                <option value="hidden">Hide address completely</option>
                                                <option value="city_only">Show City/Village and District only</option>
                                                <option value="full">Show Full Address</option>
                                            </select>
                                            <InputError class="mt-2" :message="form.errors.address_visibility" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Matrimonial Directory Privacy -->
                            <div class="bg-pink-50 p-6 rounded-lg border border-pink-100">
                                <h4 class="font-semibold text-pink-800 mb-4 border-b border-pink-200 pb-2">Matrimonial Directory Visibility</h4>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded-sm border-gray-300 text-pink-600 shadow-xs focus:ring-pink-500" v-model="form.matrimonial_show_first_name_only">
                                            <span class="ml-2 text-gray-800 font-medium">Show First Name Only</span>
                                        </label>
                                        <p class="text-sm text-gray-600 ml-6 mt-1">Hide the last name/surname in the matrimonial directory for privacy.</p>
                                    </div>

                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded-sm border-gray-300 text-pink-600 shadow-xs focus:ring-pink-500" v-model="form.matrimonial_hide_income">
                                            <span class="ml-2 text-gray-800 font-medium">Hide Annual Income</span>
                                        </label>
                                    </div>

                                    <div>
                                        <InputLabel for="matrimonial_photo_visibility" value="Matrimonial Photo Visibility" />
                                        <select id="matrimonial_photo_visibility" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-xs" v-model="form.matrimonial_photo_visibility">
                                            <option value="hidden">Hidden from everyone</option>
                                            <option value="accepted_interests_only">Visible only to Accepted Interests</option>
                                            <option value="public">Visible to all registered members</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.matrimonial_photo_visibility" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-4 border-t pt-4">
                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-bold">Settings saved successfully!</p>
                                </Transition>
                                <PrimaryButton :disabled="form.processing" class="bg-gray-800 hover:bg-gray-700">
                                    Save Privacy Settings
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
