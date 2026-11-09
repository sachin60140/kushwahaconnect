<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HelpRequest;
use App\Support\HelpPreviewImage;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpRequest::publiclyVisible();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('urgency')) {
            $query->where('urgency', $request->urgency);
        }

        if ($request->filled('state')) {
            $query->where('state', 'like', '%' . $request->state . '%');
        }

        if ($request->filled('city')) {
            // The page's single "Location (State/City)" box sends `city`; match any location column.
            $location = '%' . $request->city . '%';
            $query->where(function ($q) use ($location) {
                $q->where('city', 'like', $location)
                    ->orWhere('district', 'like', $location)
                    ->orWhere('state', 'like', $location);
            });
        }

        return Inertia::render('Help/Index', [
            'requests' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => $request->only(['category', 'urgency', 'state', 'city'])
        ]);
    }

    /**
     * A single public help request, so it can be shared on social media with its
     * own link and preview (title/description in the og: tags).
     */
    public function show(int $id)
    {
        $helpRequest = HelpRequest::publiclyVisible()->findOrFail($id);

        $location = collect([$helpRequest->city, $helpRequest->district, $helpRequest->state])
            ->filter()
            ->unique()
            ->implode(', ');

        return Inertia::render('Help/Show', [
            'helpRequest' => $helpRequest->only([
                'id', 'title', 'category', 'urgency', 'status', 'description',
                'name', 'mobile', 'whatsapp', 'city', 'district', 'state', 'created_at',
            ]),
            'seo' => array_filter([
                'title' => $helpRequest->title.' - Help Request',
                'description' => trim("{$helpRequest->urgency} {$helpRequest->category}".($location ? " in {$location}" : '').': '.$helpRequest->description),
                // Per-request preview card; ?v= busts social media caches when the request changes.
                'image' => HelpPreviewImage::isSupported()
                    ? route('help.preview', ['id' => $helpRequest->id, 'v' => HelpPreviewImage::fingerprint($helpRequest)])
                    : null,
                'image_alt' => 'Help request: '.$helpRequest->title,
                'type' => 'article',
            ]),
        ]);
    }

    /**
     * The 1200x630 social preview card (og:image) for a public help request.
     */
    public function preview(int $id)
    {
        $helpRequest = HelpRequest::publiclyVisible()->findOrFail($id);
        $path = HelpPreviewImage::path($helpRequest);

        if (! $path) {
            return redirect()->to(asset('images/kushwahaconnect-social-preview.png'));
        }

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function create()
    {
        return Inertia::render('Help/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'state' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'urgency' => 'required|in:Normal,Urgent,Emergency',
            'is_public' => 'boolean'
        ]);

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        HelpRequest::create($validated);

        return redirect()->route('help.index')->with('success', 'Your help request has been submitted successfully. Our team will review it shortly.');
    }
}
