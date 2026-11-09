<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HelpRequest;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminHelpController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpRequest::with('user');

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
        return Inertia::render('Admin/HelpRequests/Create', [
            'categories' => HelpRequest::CATEGORIES,
            'urgencies' => HelpRequest::URGENCIES,
            'statuses' => HelpRequest::STATUSES,
        ]);
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
            'category' => ['required', 'string', Rule::in(HelpRequest::CATEGORIES)],
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'urgency' => ['required', Rule::in(HelpRequest::URGENCIES)],
            'status' => ['required', Rule::in(HelpRequest::STATUSES)],
            'is_public' => 'boolean',
        ]);

        // Created by an admin on someone's behalf: not tied to the admin's own account.
        $helpRequest = HelpRequest::create($validated + ['is_public' => false]);

        $live = $helpRequest->is_public && in_array($helpRequest->status, HelpRequest::PUBLIC_STATUSES, true);

        return redirect()->route('admin.help-requests.index')->with(
            'success',
            $live
                ? 'Help request created. It is now live on the Help page and can be shared.'
                : 'Help request created. It is not public yet (private or not approved).'
        );
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
