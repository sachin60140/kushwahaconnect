<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import ApexChart from 'vue3-apexcharts';

const props = defineProps({
    stats: Object,
    recentActivity: Array,
    chartData: Object
});

const statCards = [
    { name: 'Total Members', stat: props.stats.total_members, icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', color: 'text-blue-600', bg: 'bg-blue-100', route: route('admin.members') },
    { name: 'Matrimonial Profiles', stat: props.stats.total_matrimonial, icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'text-pink-600', bg: 'bg-pink-100', route: route('admin.matrimonial') },
    { name: 'Total Jobs Posted', stat: props.stats.total_jobs, icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', color: 'text-emerald-600', bg: 'bg-emerald-100', route: route('admin.jobs.index') },
    { name: 'Help Requests', stat: props.stats.total_help_requests, icon: 'M13 10V3L4 14h7v7l9-11h-7z', color: 'text-amber-600', bg: 'bg-amber-100', route: route('admin.help-requests.index') },
    { name: 'Education Resources', stat: props.stats.total_education, icon: 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z', color: 'text-indigo-600', bg: 'bg-indigo-100', route: route('admin.education.index') },
    { name: 'Medical Resources', stat: props.stats.total_medical, icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z', color: 'text-red-600', bg: 'bg-red-100', route: route('admin.medical.index') },
    { name: 'Total Volunteers', stat: props.stats.total_volunteers, icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z', color: 'text-orange-600', bg: 'bg-orange-100', route: route('admin.volunteers.index') },
    { name: 'Gallery Albums', stat: props.stats.total_gallery, icon: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', color: 'text-purple-600', bg: 'bg-purple-100', route: route('admin.gallery.index') },
];

const pendingItems = [
    { name: 'Pending Member Approvals', count: props.stats.pending_members, route: route('admin.members') },
    { name: 'Pending Matrimonial Approvals', count: props.stats.pending_matrimonial, route: route('admin.matrimonial') },
    { name: 'Pending Job Posts', count: props.stats.pending_jobs, route: route('admin.jobs.index') },
    { name: 'Pending Volunteers', count: props.stats.pending_volunteers, route: route('admin.volunteers.index') },
    { name: 'Pending Help Requests', count: props.stats.pending_help, route: route('admin.help-requests.index') },
];

// Growth Chart Options
const growthChartOptions = {
    chart: {
        type: 'area',
        height: 350,
        toolbar: { show: false },
        fontFamily: 'Inter, sans-serif',
    },
    colors: ['#4F46E5', '#EC4899'], // Indigo for members, Pink for matrimonial
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
        categories: props.chartData?.growth.categories || [],
        axisBorder: { show: false },
        axisTicks: { show: false }
    },
    yaxis: {
        title: { text: 'New Profiles' }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    legend: { position: 'top', horizontalAlign: 'right' }
};

const growthChartSeries = props.chartData?.growth.series || [];

// Demographics Chart Options
const demographicsChartOptions = {
    chart: {
        type: 'donut',
        height: 350,
        fontFamily: 'Inter, sans-serif',
    },
    labels: props.chartData?.demographics.labels || [],
    colors: ['#4F46E5', '#10B981', '#F59E0B', '#EC4899', '#8B5CF6'],
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return Math.round(val) + "%"
        }
    },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    name: { show: true },
                    value: { show: true }
                }
            }
        }
    },
    legend: { position: 'bottom' }
};

const demographicsChartSeries = props.chartData?.demographics.series || [];

// Resources Chart Options
const resourcesChartOptions = {
    chart: {
        type: 'pie',
        height: 350,
        fontFamily: 'Inter, sans-serif',
    },
    labels: props.chartData?.resources.labels || [],
    colors: ['#10B981', '#6366F1', '#EF4444'], // Emerald, Indigo, Red
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return Math.round(val) + "%"
        }
    },
    legend: { position: 'bottom' }
};

const resourcesChartSeries = props.chartData?.resources.series || [];

// Gender Chart Options
const genderChartOptions = {
    chart: {
        type: 'donut',
        height: 350,
        fontFamily: 'Inter, sans-serif',
    },
    labels: props.chartData?.gender?.labels || [],
    colors: ['#3B82F6', '#EC4899', '#8B5CF6'], // Blue for Male, Pink for Female, Purple for Other
    dataLabels: {
        enabled: true,
        formatter: function (val) {
            return Math.round(val) + "%"
        }
    },
    legend: { position: 'bottom' }
};

const genderChartSeries = props.chartData?.gender?.series || [];

</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold leading-tight text-gray-900">
                System Dashboard
            </h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Overview Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Overview</h3>
                        <p class="mt-1 text-sm text-gray-500">A high-level summary of your platform's metrics.</p>
                    </div>
                </div>

                <!-- Main Stat Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Link v-for="item in statCards" :key="item.name" :href="item.route" class="bg-white overflow-hidden shadow rounded-lg border border-gray-100 hover:shadow-md transition-shadow group">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div :class="[item.bg, item.color, 'rounded-md p-3 group-hover:scale-110 transition-transform']">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon"></path></svg>
                                    </div>
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">{{ item.name }}</dt>
                                        <dd>
                                            <div class="text-2xl font-bold text-gray-900">{{ item.stat }}</div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3 border-t border-gray-100">
                            <div class="text-sm">
                                <span class="font-medium text-indigo-600 hover:text-indigo-900">View all</span>
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Growth Chart -->
                    <div class="lg:col-span-3 xl:col-span-2 bg-white shadow rounded-lg border border-gray-100">
                        <div class="px-5 py-4 border-b border-gray-200">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Platform Growth (Last 6 Months)</h3>
                        </div>
                        <div class="p-5">
                            <ApexChart type="area" height="350" :options="growthChartOptions" :series="growthChartSeries"></ApexChart>
                        </div>
                    </div>

                    <!-- Side Charts Column -->
                    <div class="lg:col-span-3 xl:col-span-1 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-1 gap-8">
                        <!-- Demographics Chart -->
                        <div class="bg-white shadow rounded-lg border border-gray-100">
                            <div class="px-5 py-4 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Top Locations</h3>
                            </div>
                            <div class="p-5 flex justify-center items-center h-[350px]">
                                <div v-if="demographicsChartSeries.length === 0" class="text-gray-500 italic">No location data available yet.</div>
                                <ApexChart v-else type="donut" height="350" :options="demographicsChartOptions" :series="demographicsChartSeries"></ApexChart>
                            </div>
                        </div>

                        <!-- Resources Chart -->
                        <div class="bg-white shadow rounded-lg border border-gray-100">
                            <div class="px-5 py-4 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Resource Engagement</h3>
                            </div>
                            <div class="p-5 flex justify-center items-center h-[350px]">
                                <div v-if="resourcesChartSeries.reduce((a, b) => a + b, 0) === 0" class="text-gray-500 italic">No resource data available yet.</div>
                                <ApexChart v-else type="pie" height="350" :options="resourcesChartOptions" :series="resourcesChartSeries"></ApexChart>
                            </div>
                        </div>

                        <!-- Gender Chart -->
                        <div class="bg-white shadow rounded-lg border border-gray-100">
                            <div class="px-5 py-4 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Gender Distribution</h3>
                            </div>
                            <div class="p-5 flex justify-center items-center h-[350px]">
                                <div v-if="genderChartSeries.length === 0" class="text-gray-500 italic">No gender data available yet.</div>
                                <ApexChart v-else type="donut" height="350" :options="genderChartOptions" :series="genderChartSeries"></ApexChart>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Left Column: Pending Actions & Monthly Stats -->
                    <div class="lg:col-span-1 space-y-8">
                        
                        <!-- Requires Attention Panel -->
                        <div class="bg-white shadow rounded-lg border border-red-100">
                            <div class="px-5 py-4 border-b border-gray-200 bg-red-50 rounded-t-lg">
                                <h3 class="text-lg leading-6 font-medium text-red-800 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Requires Attention
                                </h3>
                            </div>
                            <ul class="divide-y divide-gray-200">
                                <li v-for="item in pendingItems" :key="item.name" class="p-4 hover:bg-gray-50 transition-colors">
                                    <Link :href="item.route" class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">{{ item.name }}</span>
                                        <span :class="[item.count > 0 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                            {{ item.count }}
                                        </span>
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Growth Stats -->
                        <div class="bg-white shadow rounded-lg border border-gray-100">
                            <div class="px-5 py-4 border-b border-gray-200">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Monthly Growth</h3>
                            </div>
                            <div class="p-5 grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-100">
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">New Members</p>
                                    <p class="text-3xl font-bold text-indigo-600">{{ stats.new_members_this_month }}</p>
                                    <p class="text-xs text-gray-400 mt-1">This month</p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg text-center border border-gray-100">
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wider mb-1">New Matrimonial</p>
                                    <p class="text-3xl font-bold text-pink-600">{{ stats.new_matrimonial_this_month }}</p>
                                    <p class="text-xs text-gray-400 mt-1">This month</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Recent Activity Feed -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow rounded-lg border border-gray-100 h-full">
                            <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Activity Feed</h3>
                                <span class="text-sm text-gray-500">Latest 8 actions</span>
                            </div>
                            <div class="p-0">
                                <ul class="divide-y divide-gray-200">
                                    <li v-for="(activity, index) in recentActivity" :key="index" class="p-5 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex-shrink-0">
                                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full"
                                                    :class="{
                                                        'bg-blue-100 text-blue-600': activity.type === 'Member Registration',
                                                        'bg-pink-100 text-pink-600': activity.type === 'Matrimonial Profile',
                                                        'bg-amber-100 text-amber-600': activity.type === 'Help Request'
                                                    }">
                                                    <svg v-if="activity.type === 'Member Registration'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                    <svg v-if="activity.type === 'Matrimonial Profile'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                                    <svg v-if="activity.type === 'Help Request'" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">
                                                    {{ activity.name }}
                                                </p>
                                                <p class="text-sm text-gray-500 truncate">
                                                    Submitted a new <span class="font-semibold">{{ activity.type }}</span>
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0 text-right">
                                                <p class="text-sm text-gray-500 mb-1">{{ activity.time }}</p>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 uppercase" v-if="activity.status.toLowerCase() === 'pending'">
                                                    Pending
                                                </span>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 uppercase" v-else-if="activity.status.toLowerCase() === 'approved'">
                                                    Approved
                                                </span>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 uppercase" v-else>
                                                    {{ activity.status }}
                                                </span>
                                            </div>
                                            <div class="flex-shrink-0 ml-4">
                                                <Link :href="activity.url" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">View &rarr;</Link>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="recentActivity.length === 0" class="p-8 text-center text-gray-500">
                                        No recent activity to display.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
