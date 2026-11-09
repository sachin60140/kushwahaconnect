<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    profile_for: props.profile.profile_for || 'Self',
    full_name: props.profile.full_name || '',
    // Public registration and the directory filter use 'Male'/'Female'; older saves from this form used lowercase.
    gender: { male: 'Male', female: 'Female' }[String(props.profile.gender || 'male').toLowerCase()] || props.profile.gender,
    dob: props.profile.dob || '',
    height: props.profile.height || '',
    marital_status: props.profile.marital_status || 'Never Married',
    manglik: props.profile.manglik || 'No',
    gotra: props.profile.gotra || '',
    mother_tongue: props.profile.mother_tongue || '',
    current_city: props.profile.current_city || '',
    native_place: props.profile.native_place || '',
    education: props.profile.education || '',
    college: props.profile.college || '',
    occupation: props.profile.occupation || '',
    company: props.profile.company || '',
    annual_income: props.profile.annual_income || '',
    work_location: props.profile.work_location || '',
    father_name: props.profile.father_name || '',
    father_occupation: props.profile.father_occupation || '',
    mother_name: props.profile.mother_name || '',
    mother_occupation: props.profile.mother_occupation || '',
    brothers: props.profile.brothers || 0,
    sisters: props.profile.sisters || 0,
    family_type: props.profile.family_type || '',
    family_location: props.profile.family_location || '',
    pref_age_range: props.profile.pref_age_range || '',
    pref_height: props.profile.pref_height || '',
    pref_education: props.profile.pref_education || '',
    pref_location: props.profile.pref_location || '',
    pref_profession: props.profile.pref_profession || '',
    other_expectations: props.profile.other_expectations || '',
    mobile: props.profile.mobile || '',
    whatsapp: props.profile.whatsapp || '',
    email: props.profile.email || '',
});

const submit = () => {
    form.post(route('user.matrimonial-profile.update'));
};
</script>

<template>
    <Head title="Matrimonial Profile" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Matrimonial Profile
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Manage Matrimonial Profile</h3>
                            <p class="mt-1 text-sm text-gray-600">Provide details for matchmaking. This profile will be visible in the Matrimonial Directory.</p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6 max-w-4xl">
                            
                            <!-- Basic Details -->
                            <div class="bg-pink-50 p-4 rounded-lg border border-pink-100">
                                <h4 class="font-semibold text-pink-800 mb-4 border-b border-pink-200 pb-2">Basic Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <InputLabel for="profile_for" value="Profile Creating For *" />
                                        <select id="profile_for" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.profile_for" required>
                                            <option value="Self">Self</option>
                                            <option value="Son">Son</option>
                                            <option value="Daughter">Daughter</option>
                                            <option value="Brother">Brother</option>
                                            <option value="Sister">Sister</option>
                                            <option value="Relative">Relative</option>
                                            <option value="Friend">Friend</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.profile_for" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="full_name" value="Candidate's Full Name *" />
                                        <TextInput id="full_name" type="text" class="mt-1 block w-full" v-model="form.full_name" required />
                                        <InputError class="mt-2" :message="form.errors.full_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="gender" value="Gender *" />
                                        <select id="gender" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.gender" required>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.gender" />
                                    </div>
                                    <div>
                                        <InputLabel for="dob" value="Date of Birth *" />
                                        <TextInput id="dob" type="date" class="mt-1 block w-full" v-model="form.dob" required />
                                        <InputError class="mt-2" :message="form.errors.dob" />
                                    </div>
                                    <div>
                                        <InputLabel for="marital_status" value="Marital Status *" />
                                        <select id="marital_status" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.marital_status" required>
                                            <option value="Never Married">Never Married</option>
                                            <option value="Awaiting Divorce">Awaiting Divorce</option>
                                            <option value="Divorced">Divorced</option>
                                            <option value="Widowed">Widowed</option>
                                            <option value="Annulled">Annulled</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.marital_status" />
                                    </div>
                                    <div>
                                        <InputLabel for="height" value="Height" />
                                        <TextInput id="height" type="text" class="mt-1 block w-full" v-model="form.height" placeholder="e.g. 5' 8&quot;" />
                                        <InputError class="mt-2" :message="form.errors.height" />
                                    </div>
                                    <div>
                                        <InputLabel for="manglik" value="Manglik Status" />
                                        <select id="manglik" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.manglik">
                                            <option value="No">No</option>
                                            <option value="Yes">Yes</option>
                                            <option value="Don't Know">Don't Know</option>
                                            <option value="Anshik">Anshik</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.manglik" />
                                    </div>
                                    <div>
                                        <InputLabel for="gotra" value="Gotra / Sub-caste" />
                                        <TextInput id="gotra" type="text" class="mt-1 block w-full" v-model="form.gotra" />
                                        <InputError class="mt-2" :message="form.errors.gotra" />
                                    </div>
                                </div>
                            </div>

                            <!-- Education & Career -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Education & Career</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="education" value="Highest Education" />
                                        <TextInput id="education" type="text" class="mt-1 block w-full" v-model="form.education" />
                                        <InputError class="mt-2" :message="form.errors.education" />
                                    </div>
                                    <div>
                                        <InputLabel for="college" value="College / University" />
                                        <TextInput id="college" type="text" class="mt-1 block w-full" v-model="form.college" />
                                        <InputError class="mt-2" :message="form.errors.college" />
                                    </div>
                                    <div>
                                        <InputLabel for="occupation" value="Occupation / Profession" />
                                        <TextInput id="occupation" type="text" class="mt-1 block w-full" v-model="form.occupation" />
                                        <InputError class="mt-2" :message="form.errors.occupation" />
                                    </div>
                                    <div>
                                        <InputLabel for="company" value="Company Name" />
                                        <TextInput id="company" type="text" class="mt-1 block w-full" v-model="form.company" />
                                        <InputError class="mt-2" :message="form.errors.company" />
                                    </div>
                                    <div>
                                        <InputLabel for="annual_income" value="Annual Income" />
                                        <TextInput id="annual_income" type="text" class="mt-1 block w-full" v-model="form.annual_income" placeholder="e.g. 5-7 Lakhs" />
                                        <InputError class="mt-2" :message="form.errors.annual_income" />
                                    </div>
                                    <div>
                                        <InputLabel for="work_location" value="Work Location (City)" />
                                        <TextInput id="work_location" type="text" class="mt-1 block w-full" v-model="form.work_location" />
                                        <InputError class="mt-2" :message="form.errors.work_location" />
                                    </div>
                                </div>
                            </div>

                            <!-- Family Details -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Family Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="father_name" value="Father's Name" />
                                        <TextInput id="father_name" type="text" class="mt-1 block w-full" v-model="form.father_name" />
                                        <InputError class="mt-2" :message="form.errors.father_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="father_occupation" value="Father's Occupation" />
                                        <TextInput id="father_occupation" type="text" class="mt-1 block w-full" v-model="form.father_occupation" />
                                        <InputError class="mt-2" :message="form.errors.father_occupation" />
                                    </div>
                                    <div>
                                        <InputLabel for="mother_name" value="Mother's Name" />
                                        <TextInput id="mother_name" type="text" class="mt-1 block w-full" v-model="form.mother_name" />
                                        <InputError class="mt-2" :message="form.errors.mother_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="mother_occupation" value="Mother's Occupation" />
                                        <TextInput id="mother_occupation" type="text" class="mt-1 block w-full" v-model="form.mother_occupation" />
                                        <InputError class="mt-2" :message="form.errors.mother_occupation" />
                                    </div>
                                    <div>
                                        <InputLabel for="brothers" value="Number of Brothers" />
                                        <TextInput id="brothers" type="number" min="0" class="mt-1 block w-full" v-model="form.brothers" />
                                        <InputError class="mt-2" :message="form.errors.brothers" />
                                    </div>
                                    <div>
                                        <InputLabel for="sisters" value="Number of Sisters" />
                                        <TextInput id="sisters" type="number" min="0" class="mt-1 block w-full" v-model="form.sisters" />
                                        <InputError class="mt-2" :message="form.errors.sisters" />
                                    </div>
                                    <div>
                                        <InputLabel for="family_type" value="Family Type" />
                                        <select id="family_type" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.family_type">
                                            <option value="">Select</option>
                                            <option value="Joint">Joint Family</option>
                                            <option value="Nuclear">Nuclear Family</option>
                                            <option value="Others">Others</option>
                                        </select>
                                        <InputError class="mt-2" :message="form.errors.family_type" />
                                    </div>
                                    <div>
                                        <InputLabel for="family_location" value="Family Location (City/Town)" />
                                        <TextInput id="family_location" type="text" class="mt-1 block w-full" v-model="form.family_location" />
                                        <InputError class="mt-2" :message="form.errors.family_location" />
                                    </div>
                                </div>
                            </div>

                            <!-- Partner Preferences -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Partner Preferences</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="pref_age_range" value="Preferred Age Range" />
                                        <TextInput id="pref_age_range" type="text" class="mt-1 block w-full" v-model="form.pref_age_range" placeholder="e.g. 24-28" />
                                        <InputError class="mt-2" :message="form.errors.pref_age_range" />
                                    </div>
                                    <div>
                                        <InputLabel for="pref_height" value="Preferred Height" />
                                        <TextInput id="pref_height" type="text" class="mt-1 block w-full" v-model="form.pref_height" placeholder="e.g. 5'2&quot; to 5'6&quot;" />
                                        <InputError class="mt-2" :message="form.errors.pref_height" />
                                    </div>
                                    <div>
                                        <InputLabel for="pref_education" value="Preferred Education" />
                                        <TextInput id="pref_education" type="text" class="mt-1 block w-full" v-model="form.pref_education" />
                                        <InputError class="mt-2" :message="form.errors.pref_education" />
                                    </div>
                                    <div>
                                        <InputLabel for="pref_profession" value="Preferred Profession" />
                                        <TextInput id="pref_profession" type="text" class="mt-1 block w-full" v-model="form.pref_profession" />
                                        <InputError class="mt-2" :message="form.errors.pref_profession" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="pref_location" value="Preferred Location" />
                                        <TextInput id="pref_location" type="text" class="mt-1 block w-full" v-model="form.pref_location" />
                                        <InputError class="mt-2" :message="form.errors.pref_location" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="other_expectations" value="Other Expectations" />
                                        <textarea id="other_expectations" class="mt-1 block w-full border-gray-300 focus:border-pink-500 focus:ring-pink-500 rounded-md shadow-sm" v-model="form.other_expectations" rows="3"></textarea>
                                        <InputError class="mt-2" :message="form.errors.other_expectations" />
                                    </div>
                                </div>
                            </div>

                            <!-- Contact info for Matchmaking -->
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h4 class="font-semibold text-gray-800 mb-4 border-b pb-2">Contact Info for Matchmaking</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="mobile" value="Contact Mobile Number *" />
                                        <TextInput id="mobile" type="text" class="mt-1 block w-full bg-gray-100" v-model="form.mobile" required readonly />
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

                            <div class="flex items-center justify-end gap-4 border-t pt-4">
                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 font-bold">Profile updated successfully!</p>
                                </Transition>
                                <PrimaryButton :disabled="form.processing" class="bg-pink-600 hover:bg-pink-700 focus:bg-pink-700 border-none">
                                    Save Matrimonial Profile
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
