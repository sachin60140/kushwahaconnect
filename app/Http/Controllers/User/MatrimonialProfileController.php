<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MatrimonialProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MatrimonialProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $profile = MatrimonialProfile::firstOrNew(['user_id' => $user->id]);

        if (!$profile->exists) {
            // Default values
            $profile->full_name = $user->name;
            $profile->mobile = $user->mobile;
            $profile->email = $user->email;
            $profile->profile_for = 'Self';
            $profile->marital_status = 'Never Married';
        }

        return Inertia::render('User/MatrimonialProfile/Edit', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'profile_for' => 'required|string',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|string',
            'dob' => 'required|date',
            'height' => 'nullable|string',
            'marital_status' => 'required|string',
            'manglik' => 'nullable|string',
            'gotra' => 'nullable|string',
            'mother_tongue' => 'nullable|string',
            'current_city' => 'nullable|string',
            'native_place' => 'nullable|string',
            'education' => 'nullable|string',
            'college' => 'nullable|string',
            'occupation' => 'nullable|string',
            'company' => 'nullable|string',
            'annual_income' => 'nullable|string',
            'work_location' => 'nullable|string',
            'father_name' => 'nullable|string',
            'father_occupation' => 'nullable|string',
            'mother_name' => 'nullable|string',
            'mother_occupation' => 'nullable|string',
            'brothers' => 'nullable|integer',
            'sisters' => 'nullable|integer',
            'family_type' => 'nullable|string',
            'family_location' => 'nullable|string',
            'pref_age_range' => 'nullable|string',
            'pref_height' => 'nullable|string',
            'pref_education' => 'nullable|string',
            'pref_location' => 'nullable|string',
            'pref_profession' => 'nullable|string',
            'other_expectations' => 'nullable|string',
            'mobile' => 'required|string',
            'whatsapp' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $profile = MatrimonialProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        // Auto approve if needed
        $profile->status = 'approved';
        $profile->is_public = true;
        $profile->save();

        return redirect()->route('dashboard')->with('status', 'Matrimonial profile updated successfully.');
    }
}
