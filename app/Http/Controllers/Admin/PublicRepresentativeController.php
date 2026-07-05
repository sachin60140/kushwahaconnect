<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PublicRepresentative;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PublicRepresentativeController extends Controller
{
    public function index(Request $request)
    {
        $query = PublicRepresentative::query()->latest();

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('current_designation', 'like', "%{$search}%")
                    ->orWhere('political_party', 'like', "%{$search}%")
                    ->orWhere('constituency', 'like', "%{$search}%");
            });
        }

        foreach (['representative_status', 'category', 'state', 'district', 'political_party'] as $field) {
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

        return Inertia::render('Admin/Representatives/Index', [
            'representatives' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'representative_status', 'category', 'state', 'district', 'political_party', 'status']),
            'categories' => PublicRepresentative::CATEGORIES,
            'statuses' => PublicRepresentative::STATUSES,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Representatives/Form', [
            'representative' => null,
            'categories' => PublicRepresentative::CATEGORIES,
            'statuses' => PublicRepresentative::STATUSES,
            'states' => $this->statesForForm(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRepresentative($request);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = ImageUpload::storeProfilePhoto($request->file('photo'), 'public-representatives');
        }

        PublicRepresentative::create($validated);

        return redirect()->route('admin.representatives.index')->with('success', 'Public representative profile created.');
    }

    public function edit(PublicRepresentative $representative)
    {
        return Inertia::render('Admin/Representatives/Form', [
            'representative' => $representative,
            'categories' => PublicRepresentative::CATEGORIES,
            'statuses' => PublicRepresentative::STATUSES,
            'states' => $this->statesForForm(),
        ]);
    }

    public function update(Request $request, PublicRepresentative $representative)
    {
        $validated = $this->validateRepresentative($request);

        if ($request->hasFile('photo')) {
            if ($representative->photo_path) {
                Storage::disk('public')->delete($representative->photo_path);
            }

            $validated['photo_path'] = ImageUpload::storeProfilePhoto($request->file('photo'), 'public-representatives');
        }

        $representative->update($validated);

        return redirect()->route('admin.representatives.index')->with('success', 'Public representative profile updated.');
    }

    public function archive(PublicRepresentative $representative)
    {
        $representative->update([
            'is_published' => false,
            'archived_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Representative profile archived.');
    }

    private function validateRepresentative(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'photo' => 'nullable|image|max:4096',
            'representative_status' => ['required', Rule::in(PublicRepresentative::STATUSES)],
            'category' => ['required', 'string', Rule::in(PublicRepresentative::CATEGORIES)],
            'current_designation' => 'nullable|string|max:255',
            'former_designations' => 'nullable|string',
            'political_party' => 'nullable|string|max:255',
            'constituency' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'house_or_body' => 'nullable|string|max:255',
            'term_start_date' => 'nullable|date',
            'term_end_date' => 'nullable|date|after_or_equal:term_start_date',
            'biography_hi' => 'nullable|string',
            'biography_en' => 'nullable|string',
            'official_website' => 'nullable|url|max:255',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'linkedin_url' => 'nullable|url|max:255',
            'youtube_url' => 'nullable|url|max:255',
            'x_url' => 'nullable|url|max:255',
            'government_source_url' => 'nullable|url|max:255',
            'election_source_url' => 'nullable|url|max:255',
            'last_verified_at' => 'nullable|date',
            'verification_note' => 'nullable|string',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'display_order' => 'nullable|integer|min:0',
            'is_published' => 'boolean',
        ]);
    }

    private function statesForForm()
    {
        return \App\Models\State::with([
            'districts' => fn ($query) => $query
                ->where('status', true)
                ->orderBy('name_en')
                ->select(['id', 'state_id', 'name_en', 'name_hi']),
        ])
            ->where('status', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_hi']);
    }
}
