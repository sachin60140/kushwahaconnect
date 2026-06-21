<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\District;
use Illuminate\Http\Request;
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
            'mobile' => 'required|string|max:20',
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
        ]);

        $profile = MemberProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        // Auto-approve user profile logic can be added here if needed based on the previous request
        // "proceed with auto approved no approval required from my side"
        $profile->status = 'approved';
        $profile->is_public = true;
        $profile->save();

        $user = $request->user();
        if ($user->status === 'profile_incomplete') {
            $user->status = 'approved';
        }
        
        // Sync mobile number to user record
        if ($user->mobile !== $validated['mobile']) {
            $user->mobile = $validated['mobile'];
        }
        $user->save();

        return redirect()->route('dashboard')->with('status', 'Community profile updated successfully.');
    }
}
