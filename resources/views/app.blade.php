<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @php
            $seo = \App\Support\Seo::make($page['props']['seo'] ?? [], request());
            $googleAnalyticsId = 'G-SXSLYEZHM7';
            $analyticsPrivatePatterns = [
                'admin*',
                'dashboard*',
                'my-*',
                'profile*',
                'secure-file*',
                'matrimonial-directory*',
                'matrimonial-photo*',
                'jobs/create*',
                'login*',
                'register*',
                'forgot-password*',
                'reset-password*',
                'verify-email*',
                'confirm-password*',
            ];
            $showGoogleAnalytics = ! request()->is($analyticsPrivatePatterns);
        @endphp
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="keywords" content="{{ $seo['keywords'] }}">
        <meta name="robots" content="{{ $seo['robots'] }}">
        <link rel="canonical" href="{{ $seo['url'] }}">

        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:site_name" content="{{ $seo['site_name'] }}">
        <meta property="og:locale" content="{{ $seo['locale'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['url'] }}">
        <meta property="og:image" content="{{ $seo['image'] }}">
        <meta property="og:image:secure_url" content="{{ $seo['image'] }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $seo['image_alt'] }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        <meta name="twitter:image" content="{{ $seo['image'] }}">
        <meta name="twitter:image:alt" content="{{ $seo['image_alt'] }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @if ($showGoogleAnalytics)
            <!-- Google tag (gtag.js) -->
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
            <script>
                window.KUSHWAHA_CONNECT_GA_ID = @json($googleAnalyticsId);
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', @json($googleAnalyticsId), {
                    send_page_view: false,
                });
            </script>
        @endif
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
