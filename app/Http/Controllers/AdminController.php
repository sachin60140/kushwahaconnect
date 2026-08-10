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
            'pending_matrimonial' => MatrimonialProfile::where('status', 'pending')->count(),
            'pending_jobs' => \App\Models\JobPosting::where('status', 'pending')->count(),
            'pending_volunteers' => \App\Models\Volunteer::where('status', 'Pending')->count(),
            'pending_help' => \App\Models\HelpRequest::where('status', 'Pending')->count(),
            'pending_personality_nominations' => $hasNominations ? \App\Models\PersonalityNomination::whereIn('status', ['submitted', 'under_review', 'more_information_required'])->count() : 0,
            'pending_corrections' => $hasCorrections ? \App\Models\ProfileCorrectionRequest::whereIn('status', ['submitted', 'under_review'])->count() : 0,
            
            // New this month
            'new_members_this_month' => MemberProfile::whereMonth('created_at', now()->month)->count(),
            'new_matrimonial_this_month' => MatrimonialProfile::whereMonth('created_at', now()->month)->count(),
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
            'chartData' => $chartData
        ]);
    }

    // Members
    public function members(Request $request)
    {
        $query = MemberProfile::with(['state', 'district'])->latest();

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

        return Inertia::render('Admin/Members', [
            'members' => $query->paginate(15)->withQueryString(),
            'states' => \App\Models\State::with('districts')->orderBy('name_en')->get(),
            'filters' => $request->only(['search', 'status', 'state_id', 'district_id'])
        ]);
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
    public function matrimonial()
    {
        return Inertia::render('Admin/Matrimonial', [
            'profiles' => MatrimonialProfile::latest()->paginate(15)
        ]);
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
            \Illuminate\Support\Facades\Storage::disk('local')->delete($profile->photo);
        }
        if ($profile->id_proof) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($profile->id_proof);
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
