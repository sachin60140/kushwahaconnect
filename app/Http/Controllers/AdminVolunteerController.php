<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Volunteer;
use Inertia\Inertia;

class AdminVolunteerController extends Controller
{
    public function index(Request $request)
    {
        $query = Volunteer::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Volunteers/Index', [
            'volunteers' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $request->only(['status'])
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $volunteer = Volunteer::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected'
        ]);

        $volunteer->update($validated);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function destroy($id)
    {
        Volunteer::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Volunteer deleted successfully.');
    }
}
