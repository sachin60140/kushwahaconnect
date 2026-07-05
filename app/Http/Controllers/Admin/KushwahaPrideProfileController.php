<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KushwahaPrideProfile;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class KushwahaPrideProfileController extends Controller
{
    public function index(Request $request)
    {
        $query = KushwahaPrideProfile::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_hi', 'like', "%{$search}%")
                    ->orWhere('designation_en', 'like', "%{$search}%")
                    ->orWhere('organisation', 'like', "%{$search}%");
            });
        }

        foreach (['category', 'state', 'district'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->string($field));
            }
        }

        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'published' => $query->where('is_published', true)->whereNull('archived_at'),
                'draft' => $query->where('is_published', false)->whereNull('archived_at'),
                'archived' => $query->whereNotNull('archived_at'),
                default => null,
            };
        } else {
            $query->whereNull('archived_at');
        }

        return Inertia::render('Admin/Pride/Index', [
            'profiles' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'category', 'state', 'district', 'status']),
            'categories' => KushwahaPrideProfile::CATEGORIES,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pride/Form', [
            'profile' => null,
            'categories' => KushwahaPrideProfile::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProfile($request);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = ImageUpload::storeProfilePhoto($request->file('photo'), 'kushwaha-pride');
        }

        KushwahaPrideProfile::create($validated);

        return redirect()->route('admin.pride.index')->with('success', 'Kushwaha Pride profile created.');
    }

    public function edit(KushwahaPrideProfile $pride)
    {
        return Inertia::render('Admin/Pride/Form', [
            'profile' => $pride,
            'categories' => KushwahaPrideProfile::CATEGORIES,
        ]);
    }

    public function update(Request $request, KushwahaPrideProfile $pride)
    {
        $validated = $this->validateProfile($request);

        if ($request->hasFile('photo')) {
            if ($pride->photo_path) {
                Storage::disk('public')->delete($pride->photo_path);
            }

            $validated['photo_path'] = ImageUpload::storeProfilePhoto($request->file('photo'), 'kushwaha-pride');
        }

        $pride->update($validated);

        return redirect()->route('admin.pride.index')->with('success', 'Kushwaha Pride profile updated.');
    }

    public function archive(KushwahaPrideProfile $pride)
    {
        $pride->update([
            'is_published' => false,
            'archived_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Profile archived.');
    }

    private function validateProfile(Request $request): array
    {
        return $request->validate([
            'name_hi' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'photo' => 'nullable|image|max:4096',
            'category' => ['required', 'string', Rule::in(KushwahaPrideProfile::CATEGORIES)],
            'designation_hi' => 'nullable|string|max:255',
            'designation_en' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'biography_hi' => 'nullable|string',
            'biography_en' => 'nullable|string',
            'achievements_hi' => 'nullable|string',
            'achievements_en' => 'nullable|string',
            'organisation' => 'nullable|string|max:255',
            'official_website' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'x_url' => 'nullable|url|max:255',
            'verification_sources' => 'nullable|string',
            'last_verified_at' => 'nullable|date',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);
    }
}
