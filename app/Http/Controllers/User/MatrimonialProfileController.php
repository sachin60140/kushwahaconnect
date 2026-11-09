<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MatrimonialProfile;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            // File paths stay server-side; the page gets access-checked URLs instead.
            'profile' => $profile->makeHidden(['photo', 'id_proof', 'biodata', 'admin_note']),
            'isNew' => ! $profile->exists,
            'biodataUrl' => $profile->exists && $profile->biodata ? route('matrimonial.biodata', $profile->id) : null,
        ]);
    }

    public function update(Request $request)
    {
        $existing = MatrimonialProfile::where('user_id', $request->user()->id)->first();

        $validated = $request->validate([
            ...MatrimonialProfile::detailRules(),
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            // Mandatory when creating a profile; existing profiles may add it later.
            'biodata' => [$existing ? 'nullable' : 'required', ...MatrimonialProfile::BIODATA_RULES],
        ], [
            'biodata.required' => 'Please upload your biodata (PDF, JPG or PNG) to create your matrimonial profile.',
            'biodata.mimes' => 'Biodata must be a PDF, JPG or PNG file.',
            'biodata.max' => 'Biodata must be 5 MB or smaller.',
        ]);

        $details = collect($validated)->except(['photo', 'biodata'])->all();

        $profile = MatrimonialProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $details
        );

        if ($request->hasFile('photo')) {
            $previousPhoto = $profile->photo;
            $profile->photo = ImageUpload::storeProfilePhoto($request->file('photo'), 'matrimonial_photos', 'local');

            if ($previousPhoto) {
                Storage::disk('local')->delete($previousPhoto);
            }
        }

        // Auto-approve, but a re-save must not undo an admin's rejection.
        if ($profile->status !== 'rejected') {
            $profile->status = 'approved';
        }
        $profile->is_public = true;
        $profile->save();

        if ($request->hasFile('biodata')) {
            $profile->replaceBiodata($request->file('biodata'));
        }

        return redirect()->route('dashboard')->with('status', 'Matrimonial profile updated successfully.');
    }
}
