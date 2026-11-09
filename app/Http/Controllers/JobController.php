<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobPosting;
use Inertia\Inertia;

class JobController extends Controller
{
    public function index(Request $request)
    {
        $query = JobPosting::where('status', 'approved');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('company', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        return Inertia::render('Jobs/Index', [
            'jobs' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'location'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Jobs/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|string',
            'salary_range' => 'nullable|string|max:255',
            'contact_email' => 'required|email|max:255'
        ]);

        $request->user()->jobPostings()->create($validated);

        return redirect()->route('jobs.index')->with('success', 'Job posting submitted successfully. It will be visible once approved by an administrator.');
    }
}
