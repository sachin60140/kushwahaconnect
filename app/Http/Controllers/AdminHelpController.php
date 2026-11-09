<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HelpRequest;
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
            'filters' => $request->only(['status'])
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $helpRequest = HelpRequest::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Assigned,In Progress,Resolved,Rejected'
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
