<?php

namespace App\Http\Controllers;

use App\Rules\VerifiedMobile;
use App\Models\KushwahaPrideProfile;
use App\Models\PersonalityNomination;
use App\Models\ProfileCorrectionRequest;
use App\Models\PublicRepresentative;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PublicFigureController extends Controller
{
    public function pride(Request $request)
    {
        if (! Schema::hasTable('kushwaha_pride_profiles')) {
            return Inertia::render('Pride/Index', [
                'profiles' => $this->emptyPagination(12),
                'filters' => $request->only(['search', 'category', 'state', 'district']),
                'categories' => KushwahaPrideProfile::CATEGORIES,
                'states' => [],
                'districts' => [],
            ]);
        }

        $query = KushwahaPrideProfile::published()
            ->orderBy('display_order')
            ->latest();

        $this->applyLocationFilters($query, $request);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_hi', 'like', "%{$search}%")
                    ->orWhere('designation_en', 'like', "%{$search}%")
                    ->orWhere('designation_hi', 'like', "%{$search}%")
                    ->orWhere('organisation', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        return Inertia::render('Pride/Index', [
            'profiles' => $query->paginate(12)->withQueryString(),
            'filters' => $request->only(['search', 'category', 'state', 'district']),
            'categories' => KushwahaPrideProfile::CATEGORIES,
            'states' => $this->filterOptions(KushwahaPrideProfile::class, 'state'),
            'districts' => $this->filterOptions(KushwahaPrideProfile::class, 'district'),
        ]);
    }

    public function representatives(Request $request)
    {
        if (! Schema::hasTable('public_representatives')) {
            return Inertia::render('Representatives/Index', [
                'representatives' => $this->emptyPagination(12),
                'filters' => $request->only([
                    'search',
                    'representative_status',
                    'category',
                    'state',
                    'district',
                    'political_party',
                    'constituency',
                    'term_year',
                ]),
                'categories' => PublicRepresentative::CATEGORIES,
                'statuses' => PublicRepresentative::STATUSES,
                'states' => [],
                'districts' => [],
                'parties' => [],
                'constituencies' => [],
            ]);
        }

        $query = PublicRepresentative::published()
            ->orderBy('display_order')
            ->latest();

        $this->applyLocationFilters($query, $request);

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('current_designation', 'like', "%{$search}%")
                    ->orWhere('former_designations', 'like', "%{$search}%")
                    ->orWhere('political_party', 'like', "%{$search}%")
                    ->orWhere('constituency', 'like', "%{$search}%");
            });
        }

        foreach (['representative_status', 'category', 'political_party', 'constituency'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->string($field));
            }
        }

        if ($request->filled('term_year')) {
            $year = (int) $request->term_year;
            $query->where(function ($yearQuery) use ($year) {
                $yearQuery->whereYear('term_start_date', '<=', $year)
                    ->where(function ($termQuery) use ($year) {
                        $termQuery->whereNull('term_end_date')
                            ->orWhereYear('term_end_date', '>=', $year);
                    });
            });
        }

        return Inertia::render('Representatives/Index', [
            'representatives' => $query->paginate(12)->withQueryString(),
            'filters' => $request->only([
                'search',
                'representative_status',
                'category',
                'state',
                'district',
                'political_party',
                'constituency',
                'term_year',
            ]),
            'categories' => PublicRepresentative::CATEGORIES,
            'statuses' => PublicRepresentative::STATUSES,
            'states' => $this->filterOptions(PublicRepresentative::class, 'state'),
            'districts' => $this->filterOptions(PublicRepresentative::class, 'district'),
            'parties' => $this->filterOptions(PublicRepresentative::class, 'political_party'),
            'constituencies' => $this->filterOptions(PublicRepresentative::class, 'constituency'),
        ]);
    }

    public function nominate()
    {
        return Inertia::render('Nominations/Create', [
            'categories' => KushwahaPrideProfile::CATEGORIES,
        ]);
    }

    public function storeNomination(Request $request)
    {
        $validated = $request->validate([
            'person_name' => 'required|string|max:255',
            'category' => ['required', 'string', Rule::in(KushwahaPrideProfile::CATEGORIES)],
            'profession_or_designation' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'achievement' => 'required|string|max:5000',
            'biography' => 'nullable|string|max:8000',
            'photograph' => 'nullable|image|max:4096',
            'supporting_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'source_links' => 'nullable|string|max:5000',
            'social_links' => 'nullable|string|max:5000',
            'nominator_name' => 'required|string|max:255',
            'nominator_mobile' => ['required', 'string', 'max:30', new VerifiedMobile],
            'relationship' => 'nullable|string|max:255',
            'consent' => 'accepted',
        ]);

        if ($request->hasFile('photograph')) {
            $validated['photograph_path'] = ImageUpload::storeProfilePhoto($request->file('photograph'), 'personality-nominations/photos');
        }

        if ($request->hasFile('supporting_document')) {
            $validated['supporting_document_path'] = $request->file('supporting_document')->store('personality-nominations/documents', 'public');
        }

        unset($validated['photograph'], $validated['supporting_document']);
        $validated['status'] = 'submitted';

        PersonalityNomination::create($validated);

        return redirect()->back()->with('success', 'Nomination submitted successfully. It will be reviewed by the admin team before publication.');
    }

    public function storeCorrection(Request $request)
    {
        $validated = $request->validate([
            'profile_type' => 'required|in:personality,representative',
            'profile_id' => 'nullable|integer',
            'profile_name' => 'nullable|string|max:255',
            'requester_name' => 'required|string|max:255',
            'requester_mobile' => ['required', 'string', 'max:30', new VerifiedMobile],
            'requester_email' => 'nullable|email|max:255',
            'correction_details' => 'required|string|max:5000',
            'source_links' => 'nullable|string|max:5000',
        ]);

        ProfileCorrectionRequest::create([
            ...$validated,
            'status' => 'submitted',
        ]);

        return redirect()->back()->with('success', 'Correction request submitted. The admin team will verify it before making changes.');
    }

    private function applyLocationFilters($query, Request $request): void
    {
        foreach (['state', 'district'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->string($field));
            }
        }
    }

    private function filterOptions(string $model, string $field): array
    {
        return $model::published()
            ->whereNotNull($field)
            ->where($field, '!=', '')
            ->distinct()
            ->orderBy($field)
            ->pluck($field)
            ->values()
            ->all();
    }

    private function emptyPagination(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            [],
            0,
            $perPage,
            LengthAwarePaginator::resolveCurrentPage(),
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
