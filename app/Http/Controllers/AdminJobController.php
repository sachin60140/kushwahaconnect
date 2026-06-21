<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobPosting;
use Inertia\Inertia;

class AdminJobController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Jobs/Index', [
            'jobs' => JobPosting::with('user')->latest()->paginate(15)
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $job = JobPosting::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,closed'
        ]);

        $job->update($validated);

        return redirect()->back()->with('success', 'Job status updated successfully.');
    }

    public function destroy($id)
    {
        $job = JobPosting::findOrFail($id);
        $job->delete();

        return redirect()->back()->with('success', 'Job deleted successfully.');
    }
}
