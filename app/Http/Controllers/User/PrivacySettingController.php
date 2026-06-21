<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserPrivacySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrivacySettingController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        $settings = UserPrivacySetting::firstOrCreate(
            ['user_id' => $user->id],
            [
                'public_profile_visible' => true,
                'mobile_visibility' => 'hidden',
                'email_visibility' => 'hidden',
                'photo_visibility' => 'public',
                'address_visibility' => 'city_only',
                'matrimonial_show_first_name_only' => false,
                'matrimonial_photo_visibility' => 'public',
                'matrimonial_hide_income' => false,
            ]
        );

        return Inertia::render('User/PrivacySettings/Edit', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'public_profile_visible' => 'boolean',
            'mobile_visibility' => 'required|in:hidden,connections_only,public',
            'email_visibility' => 'required|in:hidden,connections_only,public',
            'photo_visibility' => 'required|in:hidden,connections_only,public',
            'address_visibility' => 'required|in:hidden,city_only,full',
            'matrimonial_show_first_name_only' => 'boolean',
            'matrimonial_photo_visibility' => 'required|in:hidden,accepted_interests_only,public',
            'matrimonial_hide_income' => 'boolean',
        ]);

        UserPrivacySetting::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return back()->with('status', 'Privacy settings updated successfully.');
    }
}
