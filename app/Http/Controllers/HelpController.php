<?php

namespace App\Http\Controllers;

use App\Rules\VerifiedMobile;
use Illuminate\Http\Request;
use App\Models\HelpRequest;
use App\Models\HelpRequestDocument;
use App\Support\HelpPreviewImage;
use App\Support\HelpRequestExtras;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpRequest::publiclyVisible()->withCount('documents');

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
        $helpRequest = HelpRequest::publiclyVisible()->with('documents')->findOrFail($id);

        $location = collect([$helpRequest->city, $helpRequest->district, $helpRequest->state])
            ->filter()
            ->unique()
            ->implode(', ');

        return Inertia::render('Help/Show', [
            'helpRequest' => [
                ...$helpRequest->only([
                    'id', 'title', 'category', 'urgency', 'status', 'description',
                    'name', 'mobile', 'whatsapp', 'city', 'district', 'state', 'created_at',
                    ...HelpRequestExtras::PAYMENT_FIELDS,
                ]),
                'upi_qr_url' => $helpRequest->upi_qr_url,
                'documents' => $helpRequest->documents->map->only(['id', 'original_name', 'mime_type', 'size', 'url', 'is_image'])->values(),
            ],
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

    /** A supporting document/photo: public while the request is public & approved; admins always. */
    public function document(Request $request, int $id, int $document)
    {
        $helpRequest = HelpRequest::findOrFail($id);
        $file = HelpRequestDocument::where('help_request_id', $helpRequest->id)->findOrFail($document);

        abort_unless($helpRequest->filesVisibleTo($request->user()), 404);

        return $this->serve($file->path, $file->original_name, $helpRequest->isPubliclyVisible());
    }

    /** The UPI QR image, with the same visibility rules as documents. */
    public function upiQr(Request $request, int $id)
    {
        $helpRequest = HelpRequest::findOrFail($id);

        abort_unless($helpRequest->upi_qr && $helpRequest->filesVisibleTo($request->user()), 404);

        return $this->serve($helpRequest->upi_qr, 'upi-qr-'.$helpRequest->id.'.'.pathinfo($helpRequest->upi_qr, PATHINFO_EXTENSION), $helpRequest->isPubliclyVisible());
    }

    private function serve(string $path, string $downloadName, bool $public)
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);

        $name = Str::ascii($downloadName) ?: 'file';
        $name = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name);

        $response = response()->file($disk->path($path), [
            'Content-Disposition' => 'inline; filename="'.$name.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // Files of hidden (pending/private) requests must never be stored by shared caches.
        // Set on the response object: BinaryFileResponse forces "public" over a header value.
        if ($public) {
            $response->setPublic()->setMaxAge(3600);
        } else {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-store');
        }

        return $response;
    }

    public function create()
    {
        return Inertia::render('Help/Create');
    }

    public function store(Request $request)
    {
        HelpRequestExtras::normalize($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => ['required', 'string', 'max:20', new VerifiedMobile],
            'whatsapp' => 'nullable|string|max:20',
            'state' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:'.HelpRequest::MAX_DESCRIPTION,
            'urgency' => 'required|in:Normal,Urgent,Emergency',
            'is_public' => 'boolean',
            ...collect(HelpRequestExtras::rules())->except(['remove_documents', 'remove_documents.*', 'remove_upi_qr'])->all(),
        ], HelpRequestExtras::messages());

        $data = collect($validated)
            ->only(['name', 'mobile', 'whatsapp', 'state', 'district', 'city', 'category', 'title', 'description', 'urgency', 'is_public'])
            ->merge(HelpRequestExtras::paymentData($validated))
            ->all();

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        // Public submissions stay Pending (hidden) until an admin approves them.
        // One transaction: a failed upload leaves no half-saved request behind.
        DB::transaction(function () use ($data, $request) {
            $help = HelpRequest::create($data);
            HelpRequestExtras::saveFiles($help, $request);
        });

        return redirect()->route('help.index')->with('success', 'Your help request has been submitted successfully. Our team will review it shortly.');
    }
}
