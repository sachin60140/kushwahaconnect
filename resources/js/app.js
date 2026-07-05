import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const privateAnalyticsPrefixes = [
    '/admin',
    '/dashboard',
    '/my-',
    '/profile',
    '/secure-file',
    '/matrimonial-directory',
    '/matrimonial-photo',
    '/jobs/create',
    '/login',
    '/register',
    '/forgot-password',
    '/reset-password',
    '/verify-email',
    '/confirm-password',
];

let lastTrackedAnalyticsUrl = null;

const analyticsUrl = (url = window.location.href) => {
    try {
        return new URL(url, window.location.origin);
    } catch {
        return new URL(window.location.href);
    }
};

const isPublicAnalyticsPath = (url = window.location.href) => {
    const path = analyticsUrl(url).pathname.replace(/\/+$/, '') || '/';

    return ! privateAnalyticsPrefixes.some((prefix) => {
        if (prefix.endsWith('-')) {
            return path.startsWith(prefix);
        }

        return path === prefix || path.startsWith(`${prefix}/`);
    });
};

const updateGoogleAnalyticsState = (url = window.location.href) => {
    const id = window.KUSHWAHA_CONNECT_GA_ID;

    if (! id || typeof window.gtag !== 'function') {
        return false;
    }

    const isPublic = isPublicAnalyticsPath(url);
    window[`ga-disable-${id}`] = ! isPublic;

    return isPublic;
};

const trackGoogleAnalyticsPageView = (url = window.location.href) => {
    const target = analyticsUrl(url);
    const targetUrl = target.href;

    if (! updateGoogleAnalyticsState(targetUrl) || lastTrackedAnalyticsUrl === targetUrl) {
        return;
    }

    lastTrackedAnalyticsUrl = targetUrl;

    window.gtag('event', 'page_view', {
        page_title: document.title,
        page_location: targetUrl,
        page_path: `${target.pathname}${target.search}`,
    });
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        queueMicrotask(() => trackGoogleAnalyticsPageView());

        return vueApp;
    },
    progress: {
        color: '#4B5563',
    },
});

router.on('before', (event) => {
    updateGoogleAnalyticsState(event.detail.visit.url);
});

router.on('navigate', (event) => {
    trackGoogleAnalyticsPageView(event.detail.page.url);
});
