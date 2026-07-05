<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileCorrectionRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProfileCorrectionRequest::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('profile_type')) {
            $query->where('profile_type', $request->string('profile_type'));
        }

        return Inertia::render('Admin/Corrections/Index', [
            'requests' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'profile_type']),
            'statuses' => ProfileCorrectionRequest::STATUSES,
        ]);
    }

    public function update(Request $request, ProfileCorrectionRequest $correction)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ProfileCorrectionRequest::STATUSES))],
            'admin_note' => 'nullable|string|max:5000',
        ]);

        $correction->update($validated);

        return redirect()->back()->with('success', 'Correction request updated.');
    }
}
