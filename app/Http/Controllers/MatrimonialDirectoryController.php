<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Connection;
use App\Models\MatrimonialProfile;
use App\Models\Shortlist;

class MatrimonialDirectoryController extends Controller
{
    public function index(Request $request)
    {
        $query = MatrimonialProfile::with('user.privacySetting')->where('status', 'approved');

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('marital_status')) {
            $query->where('marital_status', $request->marital_status);
        }

        if ($request->filled('education')) {
            $query->where('education', 'like', '%' . $request->education . '%');
        }

        if ($request->filled('city')) {
            $query->where('current_city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('min_age')) {
            $query->whereDate('dob', '<=', now()->subYears($request->min_age));
        }

        if ($request->filled('max_age')) {
            $query->whereDate('dob', '>=', now()->subYears($request->max_age + 1));
        }

        if ($request->filled('manglik')) {
            $query->where('manglik', $request->manglik);
        }

        if ($request->filled('occupation')) {
            $query->where('occupation', 'like', '%' . $request->occupation . '%');
        }

        $userId = $request->user()->id;
        $shortlistedIds = Shortlist::where('user_id', $userId)
            ->pluck('matrimonial_profile_id')
            ->toArray();

        $profiles = $query->latest()->paginate(12)->through(function ($profile) use ($request, $shortlistedIds) {
            $this->applyDisplayPrivacy($profile, $request);
            $profile->is_shortlisted = in_array($profile->id, $shortlistedIds);
            return $profile;
        });

        return Inertia::render('MatrimonialDirectory', [
            'profiles' => $profiles,
            'filters' => $request->only(['gender', 'marital_status', 'education', 'city', 'min_age', 'max_age', 'manglik', 'occupation'])
        ]);
    }

    public function toggleShortlist(Request $request, $id)
    {
        $profile = MatrimonialProfile::where('status', 'approved')->findOrFail($id);
        
        $shortlist = Shortlist::where('user_id', $request->user()->id)
            ->where('matrimonial_profile_id', $profile->id)
            ->first();

        if ($shortlist) {
            $shortlist->delete();
            return response()->json(['status' => 'removed']);
        } else {
            Shortlist::firstOrCreate([
                'user_id' => $request->user()->id,
                'matrimonial_profile_id' => $profile->id
            ]);
            return response()->json(['status' => 'added']);
        }
    }

    public function myShortlists(Request $request)
    {
        $userId = $request->user()->id;
        
        $shortlistedIds = Shortlist::where('user_id', $userId)
            ->pluck('matrimonial_profile_id')
            ->toArray();

        $query = MatrimonialProfile::with('user.privacySetting')
            ->where('status', 'approved')
            ->whereIn('id', $shortlistedIds);

        $profiles = $query->latest()->paginate(12)->through(function ($profile) use ($request) {
            $this->applyDisplayPrivacy($profile, $request);
            $profile->is_shortlisted = true; // Always true here since these are shortlists
            return $profile;
        });

        return Inertia::render('Matrimonial/MyShortlists', [
            'profiles' => $profiles
        ]);
    }

    private function applyDisplayPrivacy(MatrimonialProfile $profile, Request $request): void
    {
        $settings = $profile->user?->privacySetting;

        if (!$this->isOwnerOrAdmin($profile, $request) && $settings?->matrimonial_show_first_name_only) {
            $profile->full_name = str($profile->full_name)->before(' ')->toString();
        }

        if (!$this->isOwnerOrAdmin($profile, $request) && $settings?->matrimonial_hide_income) {
            $profile->annual_income = null;
        }

        $profile->photo_url = $this->canViewPhoto($profile, $request)
            ? route('matrimonial.photo', $profile->id)
            : null;
    }

    private function canViewPhoto(MatrimonialProfile $profile, Request $request): bool
    {
        if (!$profile->photo) {
            return false;
        }

        if ($this->isOwnerOrAdmin($profile, $request)) {
            return true;
        }

        if ($profile->status !== 'approved' || $profile->hide_photo) {
            return false;
        }

        $visibility = $profile->user?->privacySetting?->matrimonial_photo_visibility ?? 'public';

        if ($visibility === 'hidden') {
            return false;
        }

        if ($visibility === 'accepted_interests_only') {
            return $this->hasAcceptedConnection($request->user()->id, $profile->user_id);
        }

        return true;
    }

    private function isOwnerOrAdmin(MatrimonialProfile $profile, Request $request): bool
    {
        $user = $request->user();

        return $user && (
            $profile->user_id === $user->id ||
            in_array($user->role, ['admin', 'super_admin'], true)
        );
    }

    private function hasAcceptedConnection(int $viewerId, ?int $profileUserId): bool
    {
        if (!$profileUserId) {
            return false;
        }

        return Connection::where('status', 'accepted')
            ->where(function ($query) use ($viewerId, $profileUserId) {
                $query->where(function ($q) use ($viewerId, $profileUserId) {
                    $q->where('sender_id', $viewerId)
                        ->where('receiver_id', $profileUserId);
                })->orWhere(function ($q) use ($viewerId, $profileUserId) {
                    $q->where('sender_id', $profileUserId)
                        ->where('receiver_id', $viewerId);
                });
            })
            ->exists();
    }
}
