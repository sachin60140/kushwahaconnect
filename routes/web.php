<?php

use App\Http\Controllers\ProfileController;
use App\Models\MemberProfile;
use App\Models\MatrimonialProfile;
use App\Models\NewsEvent;
use App\Models\KushwahaPrideProfile;
use App\Models\PublicRepresentative;
use App\Models\Volunteer;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\LanguageController;

Route::get('/lang/{locale}', [LanguageController::class, 'switchLang'])->name('lang.switch');

use App\Http\Controllers\PublicController;
use App\Http\Controllers\MatrimonialDirectoryController;

Route::get('/', function (Request $request) {
    $settings = WebsiteSetting::pluck('value', 'key')->toArray();
    $publicMembers = MemberProfile::with(['district'])
        ->where('status', 'approved')
        ->where('is_public', true)
        ->whereNotNull('user_id')
        ->latest()
        ->take(4)
        ->get()
        ->map(function (MemberProfile $profile) {
            $district = $profile->district;

            return [
                'id' => $profile->id,
                'user_id' => $profile->user_id,
                'first_name' => str($profile->full_name)->before(' ')->toString() ?: $profile->full_name,
                'profession' => $profile->occupation,
                'district' => $district ? (app()->getLocale() === 'hi' ? $district->name_hi : $district->name_en) : null,
                'photo_url' => $profile->profile_photo ? asset('storage/' . $profile->profile_photo) : null,
                'verified' => true,
            ];
        });

    $contentMapper = function (NewsEvent $item) {
        return [
            'id' => $item->id,
            'title' => $item->title,
            'content' => $item->content,
            'type' => $item->type,
            'event_date' => $item->event_date?->toIso8601String(),
            'event_location' => $item->event_location,
            'registration_url' => $item->registration_url,
            'image_url' => $item->image ? asset('storage/' . $item->image) : null,
            'created_at' => $item->created_at?->toIso8601String(),
        ];
    };

    $upcomingEvents = NewsEvent::where('is_published', true)
        ->where('type', 'event')
        ->whereNotNull('event_date')
        ->where('event_date', '>=', now())
        ->orderBy('event_date')
        ->take(3)
        ->get()
        ->map($contentMapper);

    $updates = collect(['news', 'success_story', 'update'])
        ->mapWithKeys(function (string $type) use ($contentMapper) {
            return [
                $type => NewsEvent::where('is_published', true)
                    ->where('type', $type)
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map($contentMapper),
            ];
        });

    $override = function (string $key, int $fallback) use ($settings) {
        return isset($settings[$key]) && $settings[$key] !== '' ? (int) $settings[$key] : $fallback;
    };

    $prideHighlights = collect();
    $representativeHighlights = collect();

    if (Schema::hasTable('kushwaha_pride_profiles')) {
        $prideHighlights = KushwahaPrideProfile::published()
            ->where('is_featured', true)
            ->orderBy('display_order')
            ->latest()
            ->take(5)
            ->get();

        if ($prideHighlights->count() < 5) {
            $fallbackPride = KushwahaPrideProfile::published()
                ->whereNotIn('id', $prideHighlights->pluck('id'))
                ->orderBy('display_order')
                ->latest()
                ->take(5 - $prideHighlights->count())
                ->get();

            $prideHighlights = $prideHighlights->concat($fallbackPride)->values();
        }
    }

    if (Schema::hasTable('public_representatives')) {
        $representativeHighlights = PublicRepresentative::published()
            ->where('representative_status', 'current')
            ->where('is_featured', true)
            ->orderBy('display_order')
            ->latest()
            ->take(4)
            ->get();

        if ($representativeHighlights->isEmpty()) {
            $representativeHighlights = PublicRepresentative::published()
                ->where('representative_status', 'current')
                ->orderBy('display_order')
                ->latest()
                ->take(4)
                ->get();
        }
    }

    return Inertia::render('Welcome', [
        'stats' => [
            'members' => $override('stat_members', MemberProfile::where('status', 'approved')->count()),
            'districts' => $override('stat_districts', MemberProfile::where('status', 'approved')->whereNotNull('district_id')->distinct('district_id')->count('district_id')),
            'volunteers' => $override('stat_volunteers', Volunteer::whereIn('status', ['Approved', 'approved'])->count()),
            'matrimonial' => $override('stat_matrimonial', MatrimonialProfile::where('status', 'approved')->count()),
        ],
        'members' => $publicMembers,
        'upcomingEvents' => $upcomingEvents,
        'updates' => $updates,
        'prideHighlight' => [
            'featured' => $prideHighlights->first(),
            'cards' => $prideHighlights->skip(1)->values(),
        ],
        'representativeHighlights' => $representativeHighlights,
    ]);
})->name('welcome');

Route::get('/about', [PublicController::class, 'about'])->name('about');

Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'storeContact'])->name('contact.store');
Route::get('/directory', [PublicController::class, 'directory'])->name('directory');
Route::get('/news', [\App\Http\Controllers\NewsController::class, 'index'])->name('news.index');
Route::get('/jobs', [\App\Http\Controllers\JobController::class, 'index'])->name('jobs.index');
Route::get('/education', [\App\Http\Controllers\EducationController::class, 'index'])->name('education.index');
Route::get('/medical', [\App\Http\Controllers\MedicalController::class, 'index'])->name('medical.index');
Route::get('/help-each-other', [\App\Http\Controllers\HelpController::class, 'index'])->name('help.index');
Route::get('/help-each-other/request', [\App\Http\Controllers\HelpController::class, 'create'])->name('help.create');
Route::post('/help-each-other', [\App\Http\Controllers\HelpController::class, 'store'])->name('help.store');

Route::get('/volunteer-directory', [\App\Http\Controllers\VolunteerController::class, 'index'])->name('volunteer.index');
Route::get('/volunteer-registration', [\App\Http\Controllers\VolunteerController::class, 'create'])->name('volunteer.create');
Route::post('/volunteer-registration', [\App\Http\Controllers\VolunteerController::class, 'store'])->name('volunteer.store');
Route::get('/gallery', [\App\Http\Controllers\GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{id}', [\App\Http\Controllers\GalleryController::class, 'show'])->name('gallery.show');
Route::get('/api/states/{state}/districts', [PublicController::class, 'getDistricts']);

Route::get('/businesses', [\App\Http\Controllers\BusinessListingController::class, 'index'])->name('businesses.index');
Route::get('/kushwaha-pride', [\App\Http\Controllers\PublicFigureController::class, 'pride'])->name('pride.index');
Route::get('/public-representatives', [\App\Http\Controllers\PublicFigureController::class, 'representatives'])->name('representatives.index');
Route::get('/nominate-inspiring-person', [\App\Http\Controllers\PublicFigureController::class, 'nominate'])->name('nominations.create');
Route::post('/nominate-inspiring-person', [\App\Http\Controllers\PublicFigureController::class, 'storeNomination'])->name('nominations.store');
Route::post('/profile-correction-requests', [\App\Http\Controllers\PublicFigureController::class, 'storeCorrection'])->name('corrections.store');

Route::get('/privacy', [PublicController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PublicController::class, 'terms'])->name('terms');
Route::get('/data-deletion', [PublicController::class, 'dataDeletion'])->name('data-deletion');
Route::post('/data-deletion', [PublicController::class, 'storeDataDeletion'])->name('data-deletion.store');

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Storage;

Route::get('/public-uploads/{path}', fn (string $path) => \App\Support\PublicUpload::response($path))
    ->where('path', '.*')
    ->name('public.uploads.show');

Route::redirect('/admin', '/dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        if (in_array(auth()->user()->role, ['admin', 'super_admin'])) {
            return app(\App\Http\Controllers\AdminController::class)->dashboard();
        }
        return Inertia::render('Dashboard', [
            'businessListings' => auth()->user()
                ->businessListings()
                ->latest()
                ->take(5)
                ->get(['id', 'business_name', 'category', 'status', 'created_at']),
        ]);
    })->name('dashboard');
    
    // User Community Profile Routes
    Route::get('/my-community-profile', [\App\Http\Controllers\User\CommunityProfileController::class, 'edit'])->name('user.member-profile.edit');
    Route::post('/my-community-profile', [\App\Http\Controllers\User\CommunityProfileController::class, 'update'])->name('user.member-profile.update');

    // User Matrimonial Profile Routes
    Route::get('/my-matrimonial-profile', [\App\Http\Controllers\User\MatrimonialProfileController::class, 'edit'])->name('user.matrimonial-profile.edit');
    Route::post('/my-matrimonial-profile', [\App\Http\Controllers\User\MatrimonialProfileController::class, 'update'])->name('user.matrimonial-profile.update');

    // User Privacy Settings Routes
    Route::get('/my-privacy-settings', [\App\Http\Controllers\User\PrivacySettingController::class, 'edit'])->name('user.privacy-settings.edit');
    Route::post('/my-privacy-settings', [\App\Http\Controllers\User\PrivacySettingController::class, 'update'])->name('user.privacy-settings.update');

    // User Connections Routes
    Route::get('/my-connections', [\App\Http\Controllers\User\ConnectionController::class, 'index'])->name('user.connections.index');
    Route::post('/my-connections', [\App\Http\Controllers\User\ConnectionController::class, 'store'])->name('user.connections.store');
    Route::patch('/my-connections/{id}', [\App\Http\Controllers\User\ConnectionController::class, 'update'])->name('user.connections.update');
    Route::delete('/my-connections/{id}', [\App\Http\Controllers\User\ConnectionController::class, 'destroy'])->name('user.connections.destroy');
    Route::delete('/my-shortlists/{id}', [\App\Http\Controllers\User\ShortlistController::class, 'destroy'])->name('user.shortlists.destroy');

    // Business Listings User Routes
    Route::get('/my-business/create', [\App\Http\Controllers\BusinessListingController::class, 'create'])->name('businesses.create');
    Route::post('/my-business', [\App\Http\Controllers\BusinessListingController::class, 'store'])->name('businesses.store');

    // Connection Routes
    Route::get('/my-notifications', [\App\Http\Controllers\User\NotificationController::class, 'index'])->name('user.notifications.index');
    Route::patch('/my-notifications/{id}/read', [\App\Http\Controllers\User\NotificationController::class, 'markAsRead'])->name('user.notifications.read');
    Route::post('/my-notifications/read-all', [\App\Http\Controllers\User\NotificationController::class, 'markAllAsRead'])->name('user.notifications.read-all');
    Route::delete('/my-notifications/{id}', [\App\Http\Controllers\User\NotificationController::class, 'destroy'])->name('user.notifications.destroy');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/members', [AdminController::class, 'members'])->name('admin.members');
    Route::get('/admin/members/export', [AdminController::class, 'exportMembers'])->name('admin.members.export');
    Route::patch('/admin/members/{id}/status', [AdminController::class, 'updateMemberStatus'])->name('admin.members.status');
    Route::delete('/admin/members/{id}', [AdminController::class, 'deleteMember'])->name('admin.members.destroy');
    
    Route::get('/admin/matrimonial', [AdminController::class, 'matrimonial'])->name('admin.matrimonial');
    Route::get('/admin/matrimonial/export', [AdminController::class, 'exportMatrimonial'])->name('admin.matrimonial.export');
    Route::patch('/admin/matrimonial/{id}/status', [AdminController::class, 'updateMatrimonialStatus'])->name('admin.matrimonial.status');
    Route::delete('/admin/matrimonial/{id}', [AdminController::class, 'deleteMatrimonial'])->name('admin.matrimonial.destroy');
    
    Route::get('/admin/enquiries', [AdminController::class, 'enquiries'])->name('admin.enquiries');
    Route::get('/admin/enquiries/export', [AdminController::class, 'exportEnquiries'])->name('admin.enquiries.export');

    Route::resource('/admin/news', \App\Http\Controllers\AdminNewsController::class)->except(['show'])->names([
        'index' => 'admin.news.index',
        'create' => 'admin.news.create',
        'store' => 'admin.news.store',
        'edit' => 'admin.news.edit',
        'update' => 'admin.news.update',
        'destroy' => 'admin.news.destroy',
    ]);
    
    Route::get('/admin/jobs', [\App\Http\Controllers\AdminJobController::class, 'index'])->name('admin.jobs.index');
    Route::patch('/admin/jobs/{id}/status', [\App\Http\Controllers\AdminJobController::class, 'updateStatus'])->name('admin.jobs.status');
    Route::delete('/admin/jobs/{id}', [\App\Http\Controllers\AdminJobController::class, 'destroy'])->name('admin.jobs.destroy');

    Route::resource('/admin/education', \App\Http\Controllers\AdminEducationController::class)->except(['show'])->names([
        'index' => 'admin.education.index',
        'create' => 'admin.education.create',
        'store' => 'admin.education.store',
        'edit' => 'admin.education.edit',
        'update' => 'admin.education.update',
        'destroy' => 'admin.education.destroy',
    ]);

    Route::resource('/admin/medical', \App\Http\Controllers\AdminMedicalController::class)->except(['show'])->names([
        'index' => 'admin.medical.index',
        'create' => 'admin.medical.create',
        'store' => 'admin.medical.store',
        'edit' => 'admin.medical.edit',
        'update' => 'admin.medical.update',
        'destroy' => 'admin.medical.destroy',
    ]);

    Route::get('/admin/help-requests', [\App\Http\Controllers\AdminHelpController::class, 'index'])->name('admin.help-requests.index');
    Route::patch('/admin/help-requests/{id}/status', [\App\Http\Controllers\AdminHelpController::class, 'updateStatus'])->name('admin.help-requests.status');
    Route::delete('/admin/help-requests/{id}', [\App\Http\Controllers\AdminHelpController::class, 'destroy'])->name('admin.help-requests.destroy');

    Route::get('/admin/volunteers', [\App\Http\Controllers\AdminVolunteerController::class, 'index'])->name('admin.volunteers.index');
    Route::patch('/admin/volunteers/{id}/status', [\App\Http\Controllers\AdminVolunteerController::class, 'updateStatus'])->name('admin.volunteers.status');
    Route::delete('/admin/volunteers/{id}', [\App\Http\Controllers\AdminVolunteerController::class, 'destroy'])->name('admin.volunteers.destroy');

    Route::resource('/admin/gallery', \App\Http\Controllers\AdminGalleryController::class)->only(['index', 'create', 'store', 'destroy'])->names([
        'index' => 'admin.gallery.index',
        'create' => 'admin.gallery.create',
        'store' => 'admin.gallery.store',
        'destroy' => 'admin.gallery.destroy',
    ]);

    Route::get('/admin/settings', [\App\Http\Controllers\SettingsController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('/admin/kushwaha-pride', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'index'])->name('admin.pride.index');
    Route::get('/admin/kushwaha-pride/create', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'create'])->name('admin.pride.create');
    Route::post('/admin/kushwaha-pride', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'store'])->name('admin.pride.store');
    Route::get('/admin/kushwaha-pride/{pride}/edit', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'edit'])->name('admin.pride.edit');
    Route::match(['put', 'patch'], '/admin/kushwaha-pride/{pride}', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'update'])->name('admin.pride.update');
    Route::patch('/admin/kushwaha-pride/{pride}/archive', [\App\Http\Controllers\Admin\KushwahaPrideProfileController::class, 'archive'])->name('admin.pride.archive');

    Route::get('/admin/public-representatives', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'index'])->name('admin.representatives.index');
    Route::get('/admin/public-representatives/create', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'create'])->name('admin.representatives.create');
    Route::post('/admin/public-representatives', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'store'])->name('admin.representatives.store');
    Route::get('/admin/public-representatives/{representative}/edit', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'edit'])->name('admin.representatives.edit');
    Route::match(['put', 'patch'], '/admin/public-representatives/{representative}', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'update'])->name('admin.representatives.update');
    Route::patch('/admin/public-representatives/{representative}/archive', [\App\Http\Controllers\Admin\PublicRepresentativeController::class, 'archive'])->name('admin.representatives.archive');

    Route::get('/admin/personality-nominations', [\App\Http\Controllers\Admin\PersonalityNominationController::class, 'index'])->name('admin.nominations.index');
    Route::patch('/admin/personality-nominations/{nomination}', [\App\Http\Controllers\Admin\PersonalityNominationController::class, 'update'])->name('admin.nominations.update');
    Route::delete('/admin/personality-nominations/{nomination}', [\App\Http\Controllers\Admin\PersonalityNominationController::class, 'destroy'])->name('admin.nominations.destroy');

    Route::get('/admin/profile-corrections', [\App\Http\Controllers\Admin\ProfileCorrectionRequestController::class, 'index'])->name('admin.corrections.index');
    Route::patch('/admin/profile-corrections/{correction}', [\App\Http\Controllers\Admin\ProfileCorrectionRequestController::class, 'update'])->name('admin.corrections.update');

    // Admin Business Listing Routes
    Route::get('/admin/businesses', [\App\Http\Controllers\Admin\BusinessListingAdminController::class, 'index'])->name('admin.business.index');
    Route::patch('/admin/businesses/{id}/status', [\App\Http\Controllers\Admin\BusinessListingAdminController::class, 'updateStatus'])->name('admin.business.update-status');
    Route::delete('/admin/businesses/{id}', [\App\Http\Controllers\Admin\BusinessListingAdminController::class, 'destroy'])->name('admin.business.destroy');

    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/secure-file/{path}', function (string $path) {
        $disk = Storage::disk('local');
        $root = realpath(storage_path('app/private'));
        $fullPath = realpath($disk->path($path));

        if (!$root || !$fullPath || !str_starts_with($fullPath, $root . DIRECTORY_SEPARATOR) || !is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    })->where('path', '.*')->name('secure.file');

    Route::get('/matrimonial-directory', [MatrimonialDirectoryController::class, 'index'])->name('matrimonial-directory.index');
    Route::post('/matrimonial-directory/{id}/shortlist', [MatrimonialDirectoryController::class, 'toggleShortlist'])->name('matrimonial-directory.shortlist');
    Route::get('/my-shortlists', [MatrimonialDirectoryController::class, 'myShortlists'])->name('my.shortlists');
    Route::get('/matrimonial-photo/{id}', [PublicController::class, 'showMatrimonialPhoto'])->name('matrimonial.photo');
    
    Route::get('/jobs/create', [\App\Http\Controllers\JobController::class, 'create'])->name('jobs.create');
    Route::post('/jobs', [\App\Http\Controllers\JobController::class, 'store'])->name('jobs.store');

});

Route::get('/join', [PublicController::class, 'join'])->name('join');
Route::post('/join', [PublicController::class, 'storeJoin'])->name('join.store');
Route::get('/matrimonial', [PublicController::class, 'matrimonial'])->name('matrimonial');
Route::post('/matrimonial', [PublicController::class, 'storeMatrimonial'])->name('matrimonial.store');

require __DIR__.'/auth.php';
