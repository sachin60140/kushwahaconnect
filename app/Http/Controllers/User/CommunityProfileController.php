<?php

namespace App\Http\Controllers\User;

use App\Rules\VerifiedMobile;
use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\District;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CommunityProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = MemberProfile::firstOrNew(['user_id' => $user->id]);

        if (!$profile->exists) {
            $profile->full_name = $user->name;
            $profile->mobile = $user->mobile;
            $profile->email = $user->email;
        }

        $states = State::orderBy('name_en')->get();
        // Return all districts, or just for the selected state if we want to optimize.
        // For simplicity, we can load all districts grouped by state or just return all and filter on frontend.
        $districts = District::all();

        return Inertia::render('User/CommunityProfile/Edit', [
            'profile' => $profile,
            'states' => $states,
            'districts' => $districts,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'gender' => 'required|string',
            'dob' => 'nullable|date',
            // Synced to users.mobile (unique) below, so validate uniqueness instead of failing with a 500.
            // A new number must be confirmed by OTP first (the account number may be re-submitted).
            'mobile' => ['required', 'string', 'max:20', Rule::unique(User::class, 'mobile')->ignore($request->user()->id), new VerifiedMobile($request->user()->mobile)],
            'whatsapp' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'state_id' => 'nullable|exists:states,id',
            'district_id' => 'nullable|exists:districts,id',
            'city_village' => 'nullable|string|max:255',
            'pin_code' => 'nullable|string|max:20',
            'current_address' => 'nullable|string',
            'native_place' => 'nullable|string',
            'education' => 'nullable|string',
            'occupation' => 'nullable|string',
            'employment_type' => 'nullable|string',
            'company_name' => 'nullable|string',
            'skills' => 'nullable|string',
            'gotra' => 'nullable|string',
            'social_work_interest' => 'nullable|string',
            'volunteer_interest' => 'boolean',
            'join_district_team' => 'boolean',
            'facebook' => 'nullable|url',
            'instagram' => 'nullable|url',
            'linkedin' => 'nullable|url',
            'twitter' => 'nullable|url',
            'youtube' => 'nullable|url',
            // Join no longer asks for a photo, so members add it here.
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        unset($validated['profile_photo']);

        $profile = MemberProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        if ($request->hasFile('profile_photo')) {
            $previous = $profile->profile_photo;
            $profile->profile_photo = \App\Support\ImageUpload::storeProfilePhoto($request->file('profile_photo'), 'profile_photos');

            if ($previous) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($previous);
            }
        }

        // Profiles are auto-approved, but a re-save must not undo an admin's rejection.
        if ($profile->status !== 'rejected') {
            $profile->status = 'approved';
        }
        $profile->is_public = true;
        $profile->save();

        $user = $request->user();
        if ($user->status === 'profile_incomplete') {
            $user->status = 'approved';
        }
        
        // Sync mobile number to user record
        // (User::booted() clears mobile_verified_at when the number changes.)
        $mobileChanged = $user->mobile !== $validated['mobile'];
        if ($mobileChanged) {
            $user->mobile = $validated['mobile'];
        }
        $user->save();

        // The new number was confirmed inline by OTP: keep the account verified.
        if ($mobileChanged && \App\Services\Otp\VerifiedMobiles::enforced() && \App\Services\Otp\VerifiedMobiles::has($user->mobile)) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        return redirect()->route('dashboard')->with('status', 'Community profile updated successfully.');
    }
}
