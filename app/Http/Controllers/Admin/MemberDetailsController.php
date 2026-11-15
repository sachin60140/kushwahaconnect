<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessListing;
use App\Models\Connection;
use App\Models\District;
use App\Models\HelpRequest;
use App\Models\MatrimonialProfile;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\User;
use App\Models\Volunteer;
use App\Support\AdminMemberMobile;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Super admins see a member's complete record and edit it. Every prop is built
 * from an explicit field list so secrets (password hash, remember token, OTP
 * columns) never reach the page.
 */
class MemberDetailsController extends Controller
{
    /** Account statuses an admin may set (see the users migration). */
    public const ACCOUNT_STATUSES = ['profile_incomplete', 'pending_approval', 'approved', 'rejected', 'suspended', 'blocked'];

    public const PROFILE_STATUSES = ['pending', 'approved', 'rejected'];

    /** Profile columns shown and edited (photo handled separately). */
    private const PROFILE_FIELDS = [
        'full_name', 'father_husband_name', 'gender', 'dob', 'mobile', 'whatsapp', 'email',
        'state_id', 'district_id', 'city_village', 'pin_code', 'current_address', 'native_place',
        'education', 'occupation', 'employment_type', 'company_name', 'skills', 'gotra',
        'social_work_interest', 'volunteer_interest', 'join_district_team',
        'facebook', 'instagram', 'linkedin', 'twitter', 'youtube',
        'is_public', 'show_mobile', 'allow_whatsapp', 'consent', 'status', 'admin_note',
    ];

    public function show(Request $request, int $id)
    {
        $member = MemberProfile::with(['state:id,name_en,name_hi', 'district:id,name_en,name_hi', 'user.privacySetting'])->findOrFail($id);
        $user = $member->user;

        return Inertia::render('Admin/MemberShow', [
            'member' => $this->profileProps($member),
            'account' => $user ? $this->accountProps($user) : null,
            'privacy' => $user ? $this->privacyProps($user) : null,
            'linked' => $user ? $this->linkedProps($user) : null,
            'logins' => $user ? $user->loginHistories()->latest('login_at')->take(10)->get()
                ->map(fn ($login) => [
                    'id' => $login->id,
                    'login_at' => $login->login_at?->toIso8601String(),
                    'ip_address' => $login->ip_address,
                    'device' => $login->device,
                    'platform' => $login->platform,
                    'browser' => $login->browser,
                    'status' => $login->status,
                ])->values() : [],
        ]);
    }

    public function edit(Request $request, int $id)
    {
        $member = MemberProfile::with('user')->findOrFail($id);
        $user = $member->user;

        return Inertia::render('Admin/MemberEdit', [
            'member' => $this->profileProps($member),
            'account' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'mobile_verified_at' => $user->mobile_verified_at?->toIso8601String(),
                'role' => $user->role,
                'status' => $user->status,
            ] : null,
            'states' => State::orderBy('name_en')->get(['id', 'name_en', 'name_hi']),
            'districts' => District::orderBy('name_en')->get(['id', 'state_id', 'name_en', 'name_hi']),
            'accountStatuses' => $this->accountStatusesFor($user),
            'profileStatuses' => self::PROFILE_STATUSES,
            'isSelf' => $user && $user->id === $request->user()->id,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $member = MemberProfile::with('user')->findOrFail($id);
        $user = $member->user;

        AdminMemberMobile::normalizeRequest($request);

        $rules = [
            // Same field rules as the member's own editor ...
            ...MemberProfile::editorRules(),
            // ... with the admin mobile rules (no OTP; verification is cleared instead).
            'mobile' => AdminMemberMobile::rules($member),
            // The email is copied to the login account, which must stay unique.
            'email' => ['nullable', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($member->user_id)],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'district_id' => [
                'nullable',
                Rule::exists('districts', 'id')->where(fn ($q) => $q->where('state_id', $request->input('state_id'))),
            ],
            'dob' => ['nullable', 'date', 'before:today'],
            // Admin-only fields.
            'status' => ['required', Rule::in(self::PROFILE_STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'is_public' => ['boolean'],
            'show_mobile' => ['boolean'],
            'allow_whatsapp' => ['boolean'],
            'remove_photo' => ['boolean'],
        ];

        if ($user) {
            $rules['account_status'] = ['required', Rule::in($this->accountStatusesFor($user))];
        }

        $validated = $request->validate($rules, [
            ...AdminMemberMobile::messages(),
            'email.unique' => 'admin_member_email_taken',
            'district_id.exists' => 'admin_member_district_state',
        ]);

        // A super admin cannot lock themselves out from this form.
        if ($user && $user->id === $request->user()->id && $validated['account_status'] !== $user->status) {
            return back()->withErrors(['account_status' => 'admin_member_own_status'])->withInput();
        }

        $changed = DB::transaction(function () use ($request, $member, $user, $validated) {
            $changed = AdminMemberMobile::apply($member, $validated['mobile']);

            $profileData = collect($validated)->only(array_diff(self::PROFILE_FIELDS, ['mobile', 'consent']))->all();
            $member->fill($profileData);

            $oldPhoto = $member->profile_photo;
            if ($request->hasFile('profile_photo')) {
                $member->profile_photo = ImageUpload::storeProfilePhoto($request->file('profile_photo'), 'profile_photos');
            } elseif (! empty($validated['remove_photo'])) {
                $member->profile_photo = null;
            }

            $changed = [...$changed, ...array_keys($member->getDirty())];
            $member->save();

            if ($oldPhoto && $oldPhoto !== $member->profile_photo) {
                Storage::disk('public')->delete($oldPhoto);
            }

            if ($user) {
                // users and member_profiles both hold name/email/mobile: keep them in sync.
                $user->name = $validated['full_name'];
                $user->email = $validated['email'] ?: null;
                $user->status = $validated['account_status'];
                if ($user->isDirty('email')) {
                    $user->email_verified_at = null;
                }
                $changed = [...$changed, ...array_map(fn ($f) => 'user.'.$f, array_keys($user->getDirty()))];
                $user->save();
            }

            return array_values(array_unique(array_diff($changed, ['updated_at'])));
        });

        Log::info('Super admin updated a member.', [
            'admin_id' => $request->user()->id,
            'member_profile_id' => $member->id,
            'user_id' => $member->user_id,
            'changed_fields' => $changed,
        ]);

        return redirect()->route('admin.members.show', $member->id)
            ->with('success', $changed ? 'admin_member_saved' : 'admin_member_no_changes');
    }

    /** @return list<string> */
    private function accountStatusesFor(?User $user): array
    {
        $statuses = self::ACCOUNT_STATUSES;
        if ($user && $user->status && ! in_array($user->status, $statuses, true)) {
            $statuses[] = $user->status;
        }

        return $statuses;
    }

    private function profileProps(MemberProfile $member): array
    {
        return [
            'id' => $member->id,
            'user_id' => $member->user_id,
            ...collect(self::PROFILE_FIELDS)->mapWithKeys(fn ($f) => [$f => $member->{$f}])->all(),
            'profile_photo_url' => $member->profile_photo ? '/storage/'.$member->profile_photo : null,
            'state_name' => $member->state?->name_en,
            'district_name' => $member->district?->name_en,
            'created_at' => $member->created_at?->toIso8601String(),
            'updated_at' => $member->updated_at?->toIso8601String(),
        ];
    }

    private function accountProps(User $user): array
    {
        $lastLogin = $user->loginHistories()->where('status', 'success')->latest('login_at')->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'mobile' => $user->mobile,
            'mobile_verified_at' => $user->mobile_verified_at?->toIso8601String(),
            'role' => $user->role,
            'status' => $user->status,
            'language_preference' => $user->language_preference,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'last_login_at' => $lastLogin?->login_at?->toIso8601String(),
        ];
    }

    private function privacyProps(User $user): array
    {
        $settings = $user->privacySetting;

        // No row yet means the member never changed the defaults (see the migration).
        return [
            'customised' => $settings !== null,
            'public_profile_visible' => (bool) ($settings->public_profile_visible ?? true),
            'mobile_visibility' => $settings->mobile_visibility ?? 'hidden',
            'email_visibility' => $settings->email_visibility ?? 'hidden',
            'photo_visibility' => $settings->photo_visibility ?? 'public',
            'address_visibility' => $settings->address_visibility ?? 'city_only',
            'matrimonial_show_first_name_only' => (bool) ($settings->matrimonial_show_first_name_only ?? false),
            'matrimonial_photo_visibility' => $settings->matrimonial_photo_visibility ?? 'public',
            'matrimonial_hide_income' => (bool) ($settings->matrimonial_hide_income ?? false),
        ];
    }

    private function linkedProps(User $user): array
    {
        $matrimonial = MatrimonialProfile::where('user_id', $user->id)->latest()->get()
            ->map(fn (MatrimonialProfile $p) => [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'gender' => $p->gender,
                'dob' => $p->dob,
                'marital_status' => $p->marital_status,
                'current_city' => $p->current_city,
                'status' => $p->status,
                'is_public' => (bool) $p->is_public,
                'created_at' => $p->created_at?->toIso8601String(),
                // Files only through the existing protected routes.
                'id_proof_url' => $p->id_proof ? route('admin.matrimonial.id-proof', $p->id) : null,
                'biodata_url' => $p->biodata ? route('matrimonial.biodata', $p->id) : null,
                'admin_url' => route('admin.matrimonial', ['search' => $p->mobile ?: $p->full_name]),
            ])->values();

        $businesses = BusinessListing::where('user_id', $user->id)->latest()
            ->get(['id', 'business_name', 'category', 'city', 'status', 'created_at'])
            ->map(fn ($b) => [
                'id' => $b->id,
                'business_name' => $b->business_name,
                'category' => $b->category,
                'city' => $b->city,
                'status' => $b->status,
                'created_at' => $b->created_at?->toIso8601String(),
                'admin_url' => route('admin.business.index', ['search' => $b->business_name]),
            ])->values();

        // Bank / UPI details on help requests are left out on purpose.
        $help = HelpRequest::where('user_id', $user->id)->latest()
            ->get(['id', 'title', 'category', 'urgency', 'status', 'created_at'])
            ->map(fn ($h) => [
                'id' => $h->id,
                'title' => $h->title,
                'category' => $h->category,
                'urgency' => $h->urgency,
                'status' => $h->status,
                'created_at' => $h->created_at?->toIso8601String(),
                'admin_url' => route('admin.help-requests.edit', $h->id),
            ])->values();

        $volunteer = Volunteer::where('user_id', $user->id)->latest()->first();

        $connections = Connection::where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'matrimonial' => $matrimonial,
            'businesses' => $businesses,
            'help_requests' => $help,
            'volunteer' => $volunteer ? [
                'id' => $volunteer->id,
                'status' => $volunteer->status,
                'skills' => $volunteer->skills,
                'city' => $volunteer->city,
                'created_at' => $volunteer->created_at?->toIso8601String(),
                'admin_url' => route('admin.volunteers.index', ['status' => $volunteer->status]),
            ] : null,
            'connections' => [
                'total' => (int) $connections->sum(),
                'accepted' => (int) ($connections['accepted'] ?? 0),
                'pending' => (int) ($connections['pending'] ?? 0),
            ],
            'shortlists' => $user->shortlists()->count(),
        ];
    }
}
