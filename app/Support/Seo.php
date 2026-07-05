<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class Seo
{
    public static function defaults(?Request $request = null): array
    {
        return [
            'title' => 'KushwahaConnect - Connecting, Supporting and Empowering the Kushwaha Community',
            'description' => 'KushwahaConnect.com is a digital community platform for Kushwaha Samaj, connecting members through community profiles, help, business networking, events, news and matrimonial services.',
            'keywords' => 'KushwahaConnect, Kushwaha Samaj, Kushwaha community, community directory, matrimonial, jobs, education, business networking, volunteering',
            'image' => asset('images/kushwahaconnect-social-preview.png'),
            'image_alt' => 'KushwahaConnect community platform preview',
            'url' => $request?->fullUrl() ?: url('/'),
            'type' => 'website',
            'site_name' => 'KushwahaConnect',
            'locale' => app()->getLocale() === 'hi' ? 'hi_IN' : 'en_IN',
            'robots' => 'index, follow',
        ];
    }

    public static function forRequest(Request $request): array
    {
        $routeName = $request->route()?->getName();

        $mapped = [
            'welcome' => [
                'title' => 'KushwahaConnect - Digital Community Platform for Kushwaha Samaj',
                'description' => 'Connect, support and empower Kushwaha Samaj through community profiles, business networking, education, help, events, news and matrimonial services.',
            ],
            'contact' => [
                'title' => 'Contact KushwahaConnect',
                'description' => 'Contact the KushwahaConnect team for community support, profile help, partnerships, feedback and platform enquiries.',
            ],
            'directory' => [
                'title' => 'Kushwaha Community Directory',
                'description' => 'Search approved public community profiles and connect with Kushwaha members, professionals, volunteers and local leaders.',
            ],
            'news.index' => [
                'title' => 'Kushwaha Community News, Events and Updates',
                'description' => 'Read latest Kushwaha Samaj news, community updates, events, success stories and announcements on KushwahaConnect.',
            ],
            'jobs.index' => [
                'title' => 'KushwahaConnect Job Board',
                'description' => 'Explore career opportunities, hiring updates and professional support from the Kushwaha community.',
            ],
            'education.index' => [
                'title' => 'Education and Career Support',
                'description' => 'Find scholarships, study resources, education guidance and career support for Kushwaha community members.',
            ],
            'medical.index' => [
                'title' => 'Medical and Emergency Support',
                'description' => 'Find medical resources, emergency support information and community help updates through KushwahaConnect.',
            ],
            'help.index' => [
                'title' => 'Help Each Other - KushwahaConnect',
                'description' => 'Request help or support community members in education, jobs, medical needs, emergency situations, business and government schemes.',
            ],
            'help.create' => [
                'title' => 'Request Help from the Community',
                'description' => 'Submit a help request to the KushwahaConnect community for education, job, medical, emergency, business or scheme support.',
            ],
            'volunteer.index' => [
                'title' => 'KushwahaConnect Volunteer Directory',
                'description' => 'Discover approved volunteers who support Kushwaha Samaj through education, jobs, medical help, emergency support and community service.',
            ],
            'volunteer.create' => [
                'title' => 'Become a KushwahaConnect Volunteer',
                'description' => 'Register as a volunteer and support Kushwaha Samaj through help, guidance, local coordination and community service.',
            ],
            'gallery.index' => [
                'title' => 'Kushwaha Community Photo Gallery',
                'description' => 'View photos and memories from Kushwaha community events, gatherings and social initiatives.',
            ],
            'businesses.index' => [
                'title' => 'Kushwaha Business Directory',
                'description' => 'Discover and support verified businesses owned by Kushwaha community members.',
            ],
            'pride.index' => [
                'title' => 'Kushwaha Pride - Verified Community Personalities',
                'description' => 'Explore verified inspiring personalities from the Kushwaha community across education, civil services, business, healthcare, sports, social service and more.',
            ],
            'representatives.index' => [
                'title' => 'Prominent Public Representatives from Kushwaha Community',
                'description' => 'View neutral, source-backed public profiles of current and former Kushwaha community representatives across India.',
            ],
            'nominations.create' => [
                'title' => 'Nominate an Inspiring Kushwaha Personality',
                'description' => 'Suggest an inspiring person from Kushwaha Samaj for admin-reviewed verification and possible publication on Kushwaha Pride.',
            ],
            'businesses.create' => [
                'title' => 'Submit Your Business',
                'description' => 'List your business on KushwahaConnect and reach community members looking for trusted services and products.',
                'robots' => 'noindex, nofollow',
            ],
            'privacy' => [
                'title' => 'Privacy Policy - KushwahaConnect',
                'description' => 'Read how KushwahaConnect collects, protects and uses member information across community, help, business and matrimonial services.',
            ],
            'terms' => [
                'title' => 'Terms and Conditions - KushwahaConnect',
                'description' => 'Read the terms for using KushwahaConnect community profiles, directory, help, business, events and matrimonial services.',
            ],
            'data-deletion' => [
                'title' => 'Request Account or Profile Data Deletion',
                'description' => 'Request deletion of your KushwahaConnect account, community profile or submitted profile data.',
            ],
            'join' => [
                'title' => 'Join KushwahaConnect Community',
                'description' => 'Create your KushwahaConnect community profile to connect, network, volunteer, get support and grow with Kushwaha Samaj.',
            ],
            'matrimonial' => [
                'title' => 'Kushwaha Matrimonial Registration',
                'description' => 'Create a verified matrimonial profile for trusted matchmaking within the Kushwaha community.',
            ],
            'matrimonial-directory.index' => [
                'title' => 'Kushwaha Matrimonial Profiles',
                'description' => 'Browse approved matrimonial profiles and find trusted matches within the Kushwaha community.',
            ],
        ];

        if ($routeName === 'about') {
            $mapped[$routeName] = [
                'title' => 'About KushwahaConnect',
                'description' => 'Learn about KushwahaConnect and its mission to unite, support and empower Kushwaha Samaj through a modern digital community platform.',
            ];
        }

        $privatePrefix = $routeName && (
            Str::startsWith($routeName, 'admin.') ||
            Str::startsWith($routeName, 'user.') ||
            Str::startsWith($routeName, 'profile.') ||
            Str::startsWith($routeName, 'password.') ||
            Str::startsWith($routeName, 'verification.') ||
            in_array($routeName, ['dashboard', 'login', 'register', 'my.shortlists'], true)
        );

        $privateSeo = $privatePrefix ? [
            'title' => 'KushwahaConnect Member Area',
            'description' => 'Secure KushwahaConnect member area for managing profiles, notifications, connections and community services.',
            'robots' => 'noindex, nofollow',
        ] : [];

        return self::make(array_merge($mapped[$routeName] ?? [], $privateSeo), $request);
    }

    public static function make(array $overrides = [], ?Request $request = null): array
    {
        $seo = array_merge(self::defaults($request), array_filter($overrides, fn ($value) => $value !== null && $value !== ''));
        $seo['title'] = self::withBrand($seo['title']);
        $seo['description'] = Str::limit(trim(strip_tags($seo['description'])), 220, '');
        $seo['keywords'] = trim(strip_tags($seo['keywords']));
        $seo['url'] = $seo['url'] ?? ($request?->fullUrl() ?: url('/'));
        $seo['image'] = self::absoluteUrl($seo['image']);

        return $seo;
    }

    private static function withBrand(string $title): string
    {
        return Str::contains(Str::lower($title), 'kushwahaconnect') ? $title : $title.' - KushwahaConnect';
    }

    private static function absoluteUrl(string $url): string
    {
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        return url($url);
    }
}
