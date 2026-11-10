<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HelpRequest;
use App\Support\HelpRequestExtras;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminHelpController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpRequest::with('user')->withCount('documents');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/HelpRequests/Index', [
            'requests' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['status']),
            'publicStatuses' => HelpRequest::PUBLIC_STATUSES,
        ]);
    }

    /**
     * Admins can record a help request on behalf of anyone (member or not),
     * e.g. someone who called or messaged for help.
     */
    public function create()
    {
        return Inertia::render('Admin/HelpRequests/Form', $this->formOptions(null));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request, 0);

        // Created by an admin on someone's behalf: not tied to the admin's own account.
        // One transaction: a failed upload leaves no half-saved request behind.
        $helpRequest = DB::transaction(function () use ($validated, $request) {
            $helpRequest = HelpRequest::create($this->requestData($validated) + ['is_public' => false]);
            HelpRequestExtras::saveFiles($helpRequest, $request);

            return $helpRequest;
        });

        return redirect()->route('admin.help-requests.index')->with(
            'success',
            $helpRequest->isPubliclyVisible()
                ? 'Help request created. It is now live on the Help page and can be shared.'
                : 'Help request created. It is not public yet (private or not approved).'
        );
    }

    /** Edit any existing request, e.g. to add documents or donation details. */
    public function edit($id)
    {
        $helpRequest = HelpRequest::with('documents')->findOrFail($id);

        return Inertia::render('Admin/HelpRequests/Form', $this->formOptions($helpRequest));
    }

    public function update(Request $request, $id)
    {
        $helpRequest = HelpRequest::withCount('documents')->findOrFail($id);

        $removing = $helpRequest->documents()
            ->whereIn('id', array_map('intval', (array) $request->input('remove_documents', [])))
            ->count();
        $validated = $this->validateRequest($request, $helpRequest->documents_count - $removing, $helpRequest);

        DB::transaction(function () use ($helpRequest, $validated, $request) {
            $helpRequest->update($this->requestData($validated));
            HelpRequestExtras::saveFiles($helpRequest, $request);
        });

        return redirect()->route('admin.help-requests.index')->with(
            'success',
            $helpRequest->isPubliclyVisible()
                ? 'Help request updated. Changes are live on the Help page.'
                : 'Help request updated. It is not public yet (private or not approved).'
        );
    }

    private function validateRequest(Request $request, int $keptDocuments, ?HelpRequest $existing = null): array
    {
        HelpRequestExtras::normalize($request);

        // Same limit as the public form; an older, longer description can still be saved unchanged.
        $descriptionLimit = max(HelpRequest::MAX_DESCRIPTION, mb_strlen((string) $existing?->description));

        return $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'state' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'category' => ['required', 'string', Rule::in(HelpRequest::CATEGORIES)],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:'.$descriptionLimit,
            'urgency' => ['required', Rule::in(HelpRequest::URGENCIES)],
            'status' => ['required', Rule::in(HelpRequest::STATUSES)],
            'is_public' => 'boolean',
            ...HelpRequestExtras::rules($keptDocuments),
        ], HelpRequestExtras::messages());
    }

    private function requestData(array $validated): array
    {
        return collect($validated)
            ->only(['name', 'mobile', 'whatsapp', 'state', 'district', 'city', 'category', 'title', 'description', 'urgency', 'status', 'is_public'])
            ->merge(HelpRequestExtras::paymentData($validated))
            ->all();
    }

    private function formOptions(?HelpRequest $helpRequest): array
    {
        return [
            'helpRequest' => $helpRequest ? [
                ...$helpRequest->only([
                    'id', 'name', 'mobile', 'whatsapp', 'state', 'district', 'city', 'category',
                    'title', 'description', 'urgency', 'status', 'is_public',
                    ...HelpRequestExtras::PAYMENT_FIELDS,
                ]),
                'upi_qr_url' => $helpRequest->upi_qr_url,
                'documents' => $helpRequest->documents->map->only(['id', 'original_name', 'mime_type', 'size', 'url', 'is_image'])->values(),
            ] : null,
            'categories' => HelpRequest::CATEGORIES,
            'urgencies' => HelpRequest::URGENCIES,
            'statuses' => HelpRequest::STATUSES,
            'maxDocuments' => HelpRequest::MAX_DOCUMENTS,
        ];
    }

    public function updateStatus(Request $request, $id)
    {
        $helpRequest = HelpRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', Rule::in(HelpRequest::STATUSES)],
        ]);

        $helpRequest->update($validated);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        HelpRequest::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Help request deleted successfully.');
    }
}
