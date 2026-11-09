<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    profile: Object,
    states: Array,
    districts: Array,
});

const form = useForm({
    full_name: props.profile.full_name || '',
    father_husband_name: props.profile.father_husband_name || '',
    // Join/Directory use 'Male'/'Female'/'Other'; older saves from this form used lowercase.
    gender: { male: 'Male', female: 'Female', other: 'Other' }[String(props.profile.gender || 'male').toLowerCase()] || props.profile.gender,
    dob: props.profile.dob || '',
    mobile: props.profile.mobile || '',
    whatsapp: props.profile.whatsapp || '',
    email: props.profile.email || '',
    state_id: props.profile.state_id || '',
    district_id: props.profile.district_id || '',
    city_village: props.profile.city_village || '',
    pin_code: props.profile.pin_code || '',
    current_address: props.profile.current_address || '',
    native_place: props.profile.native_place || '',
    education: props.profile.education || '',
    occupation: props.profile.occupation || '',
    employment_type: props.profile.employment_type || '',
    company_name: props.profile.company_name || '',
    skills: props.profile.skills || '',
    gotra: props.profile.gotra || '',
    // Stored as 0/1 (no model casts); checkboxes need real booleans to render checked.
    volunteer_interest: Boolean(props.profile.volunteer_interest),
    join_district_team: Boolean(props.profile.join_district_team),
});

const availableDistricts = ref(props.districts.filter(d => d.state_id === form.state_id));

watch(() => form.state_id, (newStateId) => {
    availableDistricts.value = props.districts.filter(d => d.state_id === newStateId);
    if (!availableDistricts.value.find(d => d.id === form.district_id)) {
        form.district_id = '';
    }
});

const submit = () => {
    form.post(route('user.member-profile.update'));
};
</script>

<template>
    <Head title="Community Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Community Directory Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Update Community Profile</h3>
                            <p class="mt-1 text-sm text-gray-600">Provide your information to connect with other community members. Your public profile will display selected information based on your privacy settings.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6 max-w-3xl">
                            
                            <!-- Personal Info -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Personal Information</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="full_name" value="Full Name *" />
                                        <TextInput id="full_name" type="text" class="mt-1 block w-full" v-model="form.full_name" required />
                                        <InputError class="mt-2" :message="form.errors.full_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="father_husband_name" value="Father's / Husband's Name" />
                                        <TextInput id="father_husband_name" type="text" class="mt-1 block w-full" v-model="form.father_husband_name" />
                                        <InputError class="mt-2" :message="form.errors.father_husband_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="gender" value="Gender *" />
                                        <select id="gender" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" v-model="form.gender" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.gender" />
                                    </div>
                                    <div>
                                        <InputLabel for="dob" value="Date of Birth" />
                                        <TextInput id="dob" type="date" class="mt-1 block w-full" v-model="form.dob" />
                                        <InputError class="mt-2" :message="form.errors.dob" />
                                    </div>
                                    <div>
                                        <InputLabel for="gotra" value="Gotra / Sub-caste" />
                                        <TextInput id="gotra" type="text" class="mt-1 block w-full" v-model="form.gotra" />
                                        <InputError class="mt-2" :message="form.errors.gotra" />
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Info -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Contact Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="mobile" value="Mobile Number *" />
                                        <TextInput id="mobile" type="text" class="mt-1 block w-full" v-model="form.mobile" required />
                                        <InputError class="mt-2" :message="form.errors.mobile" />
                                    </div>
                                    <div>
                                        <InputLabel for="whatsapp" value="WhatsApp Number" />
                                        <TextInput id="whatsapp" type="text" class="mt-1 block w-full" v-model="form.whatsapp" />
                                        <InputError class="mt-2" :message="form.errors.whatsapp" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="email" value="Email Address" />
                                        <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                                        <InputError class="mt-2" :message="form.errors.email" />
                                    </div>
                                </div>
                            </div>

                            <!-- Location Info -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Location & Address</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="state_id" value="State" />
                                        <select id="state_id" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" v-model="form.state_id">
                                            <option value="">Select State</option>
                                            <option v-for="state in states" :key="state.id" :value="state.id">{{ state.name_en }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.state_id" />
                                    </div>
                                    <div>
                                        <InputLabel for="district_id" value="District" />
                                        <select id="district_id" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" v-model="form.district_id" :disabled="!form.state_id">
                                            <option value="">Select District</option>
                                            <option v-for="district in availableDistricts" :key="district.id" :value="district.id">{{ district.name_en }}</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.district_id" />
                                    </div>
                                    <div>
                                        <InputLabel for="city_village" value="City / Village" />
                                        <TextInput id="city_village" type="text" class="mt-1 block w-full" v-model="form.city_village" />
                                        <InputError class="mt-2" :message="form.errors.city_village" />
                                    </div>
                                    <div>
                                        <InputLabel for="pin_code" value="PIN Code" />
                                        <TextInput id="pin_code" type="text" class="mt-1 block w-full" v-model="form.pin_code" />
                                        <InputError class="mt-2" :message="form.errors.pin_code" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="current_address" value="Current Full Address" />
                                        <textarea id="current_address" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" v-model="form.current_address" rows="2"></textarea>
                                        <InputError class="mt-2" :message="form.errors.current_address" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="native_place" value="Native Place (Mool Niwas)" />
                                        <TextInput id="native_place" type="text" class="mt-1 block w-full" v-model="form.native_place" placeholder="E.g., Village Name, District, State" />
                                        <InputError class="mt-2" :message="form.errors.native_place" />
                                    </div>
                                </div>
                            </div>

                            <!-- Professional Info -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Education & Profession</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="education" value="Highest Education" />
                                        <TextInput id="education" type="text" class="mt-1 block w-full" v-model="form.education" />
                                        <InputError class="mt-2" :message="form.errors.education" />
                                    </div>
                                    <div>
                                        <InputLabel for="employment_type" value="Employment Type" />
                                        <select id="employment_type" class="mt-1 block w-full border-gray-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm" v-model="form.employment_type">
                                            <option value="">Select</option>
                                            <option value="private">Private Job</option>
                                            <option value="government">Government Job</option>
                                            <option value="business">Business / Self-Employed</option>
                                            <option value="student">Student</option>
                                            <option value="not_working">Not Working</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.employment_type" />
                                    </div>
                                    <div>
                                        <InputLabel for="occupation" value="Occupation / Profession" />
                                        <TextInput id="occupation" type="text" class="mt-1 block w-full" v-model="form.occupation" />
                                        <InputError class="mt-2" :message="form.errors.occupation" />
                                    </div>
                                    <div>
                                        <InputLabel for="company_name" value="Company / Business Name" />
                                        <TextInput id="company_name" type="text" class="mt-1 block w-full" v-model="form.company_name" />
                                        <InputError class="mt-2" :message="form.errors.company_name" />
                                    </div>
                                </div>
                            </div>

                            <!-- Community Engagement -->
                            <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                <h4 class="font-semibold text-orange-800 mb-4 border-b border-orange-200 pb-2">Community Engagement</h4>
                                <div class="space-y-4">
                                    <label class="flex items-start">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500" v-model="form.volunteer_interest">
                                        <span class="ml-2 text-sm text-gray-700">I am interested in volunteering for community events and social work.</span>
                                    </label>
                                    <label class="flex items-start">
                                        <input type="checkbox" class="mt-1 rounded border-gray-300 text-orange-600 shadow-sm focus:ring-orange-500" v-model="form.join_district_team">
                                        <span class="ml-2 text-sm text-gray-700">I want to join the District Coordination Team to help build the community in my area.</span>
                                    </label>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-4 border-t pt-4">
                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-bold">Profile updated successfully!</p>
                                </Transition>
                                <PrimaryButton :disabled="form.processing" class="bg-orange-600 hover:bg-orange-700 focus:bg-orange-700">
                                    Save Profile
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
