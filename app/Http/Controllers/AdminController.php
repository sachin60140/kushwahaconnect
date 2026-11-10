<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\MemberProfile;
use App\Models\MatrimonialProfile;
use App\Models\ContactEnquiry;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MembersExport;
use App\Exports\MatrimonialExport;
use App\Exports\ContactExport;
use App\Notifications\ProfileApprovedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminController extends Controller
{
    public function dashboard()
    {
        $hasPrideProfiles = Schema::hasTable('kushwaha_pride_profiles');
        $hasRepresentatives = Schema::hasTable('public_representatives');
        $hasNominations = Schema::hasTable('personality_nominations');
        $hasCorrections = Schema::hasTable('profile_correction_requests');

        $stats = [
            // Overall Counts
            'total_members' => MemberProfile::count(),
            'total_matrimonial' => MatrimonialProfile::count(),
            'total_enquiries' => ContactEnquiry::count(),
            'total_jobs' => \App\Models\JobPosting::count(),
            'total_volunteers' => \App\Models\Volunteer::count(),
            'total_help_requests' => \App\Models\HelpRequest::count(),
            'total_education' => \App\Models\EducationResource::count(),
            'total_medical' => \App\Models\MedicalResource::count(),
            'total_gallery' => \App\Models\Gallery::count(),
            'total_pride_profiles' => $hasPrideProfiles ? \App\Models\KushwahaPrideProfile::count() : 0,
            'total_public_representatives' => $hasRepresentatives ? \App\Models\PublicRepresentative::count() : 0,
            'total_personality_nominations' => $hasNominations ? \App\Models\PersonalityNomination::count() : 0,
            
            // Pending Approvals (Requires Action)
            'pending_members' => MemberProfile::where('status', 'pending')->count(),
            'unverified_mobiles' => \App\Support\MobileVerifyReminder::unverifiedCount(),
            'pending_matrimonial' => MatrimonialProfile::where('status', 'pending')->count(),
            'pending_jobs' => \App\Models\JobPosting::where('status', 'pending')->count(),
            'pending_volunteers' => \App\Models\Volunteer::where('status', 'Pending')->count(),
            'pending_help' => \App\Models\HelpRequest::where('status', 'Pending')->count(),
            'pending_personality_nominations' => $hasNominations ? \App\Models\PersonalityNomination::whereIn('status', ['submitted', 'under_review', 'more_information_required'])->count() : 0,
            'pending_corrections' => $hasCorrections ? \App\Models\ProfileCorrectionRequest::whereIn('status', ['submitted', 'under_review'])->count() : 0,
            
            // New this month (current calendar month of the current year only)
            'new_members_this_month' => MemberProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'new_matrimonial_this_month' => MatrimonialProfile::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        // Recent Activity Feed
        $recentMembers = MemberProfile::latest()->take(5)->get()->map(function($item) {
            return [
                'type' => 'Member Registration',
                'name' => $item->full_name,
                'status' => $item->status,
                'created_at' => $item->created_at,
                'time' => clone $item->created_at,
                'url' => route('admin.members')
            ];
        });

        $recentMatrimonial = MatrimonialProfile::latest()->take(5)->get()->map(function($item) {
            return [
                'type' => 'Matrimonial Profile',
                'name' => $item->full_name,
                'status' => $item->status,
                'created_at' => $item->created_at,
                'time' => clone $item->created_at,
                'url' => route('admin.matrimonial')
            ];
        });

        $recentHelp = \App\Models\HelpRequest::with('user')->latest()->take(5)->get()->map(function($item) {
            return [
                'type' => 'Help Request',
                'name' => $item->user ? $item->user->name : 'Anonymous',
                'status' => $item->status,
                'created_at' => $item->created_at,
                'time' => clone $item->created_at,
                'url' => route('admin.help-requests.index')
            ];
        });

        $recentActivity = $recentMembers->concat($recentMatrimonial)->concat($recentHelp)
            ->sortByDesc(function($item) {
                return $item['created_at']->timestamp;
            })
            ->take(8)
            ->map(function($item) {
                $item['time'] = $item['created_at']->diffForHumans();
                unset($item['created_at']);
                return $item;
            })
            ->values();

        // Chart Data: Last 6 Months Growth
        $months = [];
        $memberGrowth = [];
        $matrimonialGrowth = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $memberGrowth[] = MemberProfile::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
                
            $matrimonialGrowth[] = MatrimonialProfile::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // Chart Data: Demographics (Top 5 States)
        $stateDemographics = MemberProfile::select('states.name_en as state', \DB::raw('count(member_profiles.id) as total'))
            ->join('states', 'member_profiles.state_id', '=', 'states.id')
            ->groupBy('states.name_en')
            ->orderByDesc('total')
            ->take(5)
            ->get();
            
        $demographicLabels = $stateDemographics->pluck('state')->toArray();
        $demographicSeries = $stateDemographics->pluck('total')->toArray();

        // Chart Data: Gender Breakdown
        $genderBreakdown = MemberProfile::select('gender', \DB::raw('count(*) as total'))
            ->groupBy('gender')
            ->get();
            
        $genderLabels = $genderBreakdown->pluck('gender')->map(function($g) { return ucfirst($g); })->toArray();
        $genderSeries = $genderBreakdown->pluck('total')->toArray();

        $chartData = [
            'growth' => [
                'categories' => $months,
                'series' => [
                    [
                        'name' => 'Community Members',
                        'data' => $memberGrowth
                    ],
                    [
                        'name' => 'Matrimonial Profiles',
                        'data' => $matrimonialGrowth
                    ]
                ]
            ],
            'demographics' => [
                'labels' => $demographicLabels,
                'series' => $demographicSeries
            ],
            'gender' => [
                'labels' => $genderLabels,
                'series' => $genderSeries
            ],
            'resources' => [
                'labels' => ['Jobs', 'Education', 'Medical'],
                'series' => [
                    \App\Models\JobPosting::count(),
                    \App\Models\EducationResource::count(),
                    \App\Models\MedicalResource::count(),
                ]
            ]
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'chartData' => $chartData,
            // SMS usage card + red banner while the gateway is degraded.
            'smsHealth' => \App\Services\Sms\SmsHealth::usage(),
        ]);
    }

    // Members
    public function members(Request $request)
    {
        $query = MemberProfile::with(['state', 'district', 'user:id,mobile,mobile_verified_at'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        if ($request->input('mobile') === 'unverified') {
            \App\Support\MobileVerifyReminder::scopeUnverified($query);
        }

        $members = $query->paginate(15)->withQueryString()->through(function (MemberProfile $member) {
            $user = $member->user;
            $member->setRelation('user', null);
            $member->setAttribute('has_account', $user !== null);
            $member->setAttribute('mobile_verified', (bool) $user?->mobile_verified_at);
            $member->setAttribute('verify_reminder_link', $user && ! $user->mobile_verified_at
                ? \App\Support\MobileVerifyReminder::whatsappLink($member->whatsapp, $user->mobile ?: $member->mobile, $member->full_name)
                : null);

            return $member;
        });

        return Inertia::render('Admin/Members', [
            'members' => $members,
            'states' => \App\Models\State::with('districts')->orderBy('name_en')->get(),
            'filters' => $request->only(['search', 'status', 'state_id', 'district_id', 'mobile']),
            'unverifiedMobileCount' => \App\Support\MobileVerifyReminder::unverifiedCount(),
            'canEditMobile' => $request->user()?->role === 'super_admin',
        ]);
    }

    /**
     * Admin corrects a member's mobile number. The account number changes too,
     * so its verification is cleared (User::booted()) and the member must verify again.
     */
    public function updateMemberMobile(Request $request, $id)
    {
        // Route is behind the super_admin middleware; checked again here as a safeguard.
        abort_unless($request->user()?->role === 'super_admin', 403);

        $member = MemberProfile::with('user')->findOrFail($id);

        $request->merge(['mobile' => \App\Services\Otp\VerifiedMobiles::normalize((string) $request->input('mobile')) ?? $request->input('mobile')]);

        $validated = $request->validate([
            'mobile' => [
                'required', 'string', 'regex:/^[6-9]\d{9}$/',
                \Illuminate\Validation\Rule::unique(\App\Models\User::class, 'mobile')->ignore($member->user_id),
            ],
        ], [
            'mobile.regex' => 'otp_error_mobile_format',
            'mobile.unique' => 'admin_mobile_taken',
        ]);

        DB::transaction(function () use ($member, $validated) {
            $member->update(['mobile' => $validated['mobile']]);

            if ($member->user && $member->user->mobile !== $validated['mobile']) {
                // A super admin's change is trusted for OTP login; verification is cleared
                // (User::booted()) and set again by the member's next OTP login or verify.
                $member->user->mobile = $validated['mobile'];
                $member->user->save();
            }
        });

        Log::info('Admin changed a member mobile number.', ['member_profile_id' => $member->id, 'admin_id' => $request->user()->id]);

        return back()->with('success', 'admin_mobile_updated');
    }

    public function exportMembers()
    {
        return Excel::download(new MembersExport, 'members.xlsx');
    }

    public function updateMemberStatus(Request $request, $id)
    {
        $validated = $request->validate(['status' => 'required|in:approved,rejected']);
        $member = MemberProfile::with('user')->findOrFail($id);

        DB::transaction(function () use ($member, $validated) {
            $member->update(['status' => $validated['status']]);

            if ($validated['status'] === 'approved' && $member->user) {
                // Keep the dashboard account status in sync with the reviewed profile.
                if (in_array($member->user->status, ['pending', 'pending_approval', 'profile_incomplete'], true)) {
                    $member->user->update(['status' => 'approved']);
                }
            }
        });

        if ($validated['status'] === 'approved' && $member->user) {
            try {
                $member->user->notify(new ProfileApprovedNotification());
            } catch (Throwable $exception) {
                Log::warning('Profile approval notification failed.', [
                    'member_profile_id' => $member->id,
                    'user_id' => $member->user->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        
        return redirect()->back()->with('success', 'Member status updated.');
    }

    public function deleteMember($id)
    {
        $member = MemberProfile::findOrFail($id);
        $member->delete();
        return redirect()->back()->with('success', 'Member deleted.');
    }

    // Matrimonial
    public function matrimonial(Request $request)
    {
        $query = MatrimonialProfile::with('user:id,name,email,mobile')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('current_city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $profiles = $query->paginate(15)->withQueryString()->through(function (MatrimonialProfile $profile) {
            $profile->setAttribute(
                'id_proof_url',
                $profile->id_proof ? route('admin.matrimonial.id-proof', $profile->id) : null
            );
            $profile->setAttribute(
                'biodata_url',
                $profile->biodata ? route('matrimonial.biodata', $profile->id) : null
            );

            return $profile;
        });

        return Inertia::render('Admin/Matrimonial', [
            'profiles' => $profiles,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /** Upload or replace a profile's biodata on the member's behalf. */
    public function uploadMatrimonialBiodata(Request $request, $id)
    {
        $profile = MatrimonialProfile::findOrFail($id);

        $request->validate([
            'biodata' => ['required', ...MatrimonialProfile::BIODATA_RULES],
        ], [
            'biodata.mimes' => 'Biodata must be a PDF, JPG or PNG file.',
            'biodata.max' => 'Biodata must be 5 MB or smaller.',
        ]);

        $hadBiodata = (bool) $profile->biodata;
        $profile->replaceBiodata($request->file('biodata'));

        return redirect()->back()->with('success', $hadBiodata ? 'Biodata replaced.' : 'Biodata uploaded.');
    }

    public function matrimonialIdProof($id)
    {
        $profile = MatrimonialProfile::findOrFail($id);

        if (!$profile->id_proof || !Storage::disk('local')->exists($profile->id_proof)) {
            abort(404);
        }

        return response()->file(Storage::disk('local')->path($profile->id_proof));
    }

    public function exportMatrimonial()
    {
        return Excel::download(new MatrimonialExport, 'matrimonial_profiles.xlsx');
    }

    public function updateMatrimonialStatus(Request $request, $id)
    {
        $profile = MatrimonialProfile::findOrFail($id);
        $request->validate(['status' => 'required|in:approved,rejected']);
        $profile->update(['status' => $request->status]);
        return redirect()->back()->with('success', 'Profile status updated.');
    }

    public function deleteMatrimonial($id)
    {
        $profile = MatrimonialProfile::findOrFail($id);
        if ($profile->photo) {
            Storage::disk('local')->delete($profile->photo);
        }
        if ($profile->id_proof) {
            Storage::disk('local')->delete($profile->id_proof);
        }
        if ($profile->biodata) {
            Storage::disk('local')->delete($profile->biodata);
        }
        $profile->delete();
        return redirect()->back()->with('success', 'Profile deleted.');
    }

    // Enquiries
    public function enquiries()
    {
        return Inertia::render('Admin/Enquiries', [
            'enquiries' => ContactEnquiry::latest()->paginate(15)
        ]);
    }

    public function exportEnquiries()
    {
        return Excel::download(new ContactExport, 'enquiries.xlsx');
    }
}
