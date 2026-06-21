<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const form = useForm({
    name: user.value ? user.value.name : '',
    mobile: user.value ? user.value.mobile : '',
    whatsapp: '',
    email: user.value ? user.value.email : '',
    state: '',
    district: '',
    city: '',
    skills: '',
    available_time: '',
    help_areas: [],
    consent: false
});

const submit = () => {
    form.post(route('volunteer.store'));
};
</script>

<template>
    <Head title="Become a Volunteer" />

    <component :is="user ? AuthenticatedLayout : PublicLayout">
        <template #header v-if="user">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-orange-500">Volunteer Network</p>
                <h2 class="mt-1 text-2xl font-black text-blue-950">Become a Volunteer</h2>
            </div>
        </template>
        <template #header v-else>
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <div class="max-w-3xl">
                    <p class="text-sm font-black uppercase tracking-[0.22em] text-orange-300">Volunteer Network</p>
                    <h1 class="mt-3 text-4xl font-black tracking-tight text-white">Become a Volunteer</h1>
                    <p class="mt-4 text-lg leading-8 text-blue-100">Join the support network and help the community in your local area.</p>
                </div>
                <Link href="/" class="kc-btn-secondary shrink-0">Back to Home</Link>
            </div>
        </template>

        <div class="py-12 bg-gray-50">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="kc-surface overflow-hidden border-t-4 border-orange-500">
                    <div class="p-8 text-gray-900">
                        <div v-if="!user" class="mb-8 text-center border-b border-gray-200 pb-6">
                            <h2 class="text-3xl font-bold text-gray-900 mb-2">Become a Community Volunteer</h2>
                            <p class="text-gray-600">Join our network of volunteers and help support the Kushwaha community in your local area.</p>
                        </div>

                        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded shadow-sm">
                            {{ $page.props.flash.success }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.name" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                    <input type="email" v-model="form.email" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.mobile" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.mobile" class="text-red-500 text-xs mt-1">{{ form.errors.mobile }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp Number</label>
                                    <input type="text" v-model="form.whatsapp" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.whatsapp" class="text-red-500 text-xs mt-1">{{ form.errors.whatsapp }}</div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2 pt-4">Location Details</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">State <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.state" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.state" class="text-red-500 text-xs mt-1">{{ form.errors.state }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">District <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.district" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.district" class="text-red-500 text-xs mt-1">{{ form.errors.district }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">City/Village <span class="text-red-500">*</span></label>
                                    <input type="text" v-model="form.city" required class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.city" class="text-red-500 text-xs mt-1">{{ form.errors.city }}</div>
                                </div>
                            </div>

                            <h3 class="text-xl font-semibold text-gray-800 border-b border-gray-100 pb-2 pt-4">Skills & Availability</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Your Skills (e.g., Teaching, Medical, Legal, IT)</label>
                                    <input type="text" v-model="form.skills" placeholder="Comma separated..." class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                    <div v-if="form.errors.skills" class="text-red-500 text-xs mt-1">{{ form.errors.skills }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Available Time</label>
                                    <select v-model="form.available_time" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 sm:text-base py-2 px-3 transition-colors duration-200">
                                        <option value="">Select availability</option>
                                        <option value="Weekends Only">Weekends Only</option>
                                        <option value="Evening Hours">Evening Hours</option>
                                        <option value="Flexible / As Needed">Flexible / As Needed</option>
                                        <option value="Full Time (During Crisis)">Full Time (During Crisis)</option>
                                    </select>
                                    <div v-if="form.errors.available_time" class="text-red-500 text-xs mt-1">{{ form.errors.available_time }}</div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Areas of Support (Select all that apply)</label>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="education_support" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Education Support</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="job_support" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Job/Career Support</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="medical_support" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Medical Support</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="event_support" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Event Organizing</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="social_media_support" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Social Media/IT Support</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" v-model="form.help_areas" value="blood_donor" class="rounded border-gray-300 text-green-600 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200">
                                        <span class="ml-2 text-sm text-gray-600">Blood Donor</span>
                                    </label>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 mt-6">
                                <label class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input type="checkbox" v-model="form.consent" required class="focus:ring focus:ring-green-200 h-4 w-4 text-green-600 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <span class="font-medium text-gray-700">I agree to the <Link href="/terms" class="text-green-600 underline">Terms and Conditions</Link></span>
                                        <p class="text-gray-500 text-xs mt-1">By registering as a volunteer, you agree to represent the community respectfully and assist others without any commercial interest.</p>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end gap-4 border-t border-gray-200 pt-6 mt-6">
                                <Link :href="route('welcome')" class="bg-white border border-gray-300 text-gray-700 font-medium px-6 py-2.5 rounded-lg shadow-sm hover:bg-gray-50 transition-colors duration-200">Cancel</Link>
                                <button type="submit" :disabled="form.processing" class="bg-orange-500 text-white font-bold px-6 py-2.5 rounded-lg shadow hover:bg-orange-600 disabled:opacity-50 transition-colors duration-200">Submit Registration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>
