<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HelpRequest;
use Inertia\Inertia;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $query = HelpRequest::where('is_public', true)
            ->whereIn('status', ['Approved', 'Assigned', 'In Progress', 'Resolved']);

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
            $query->where('city', 'like', '%' . $request->city . '%');
        }

        return Inertia::render('Help/Index', [
            'requests' => $query->latest()->paginate(12),
            'filters' => $request->only(['category', 'urgency', 'state', 'city'])
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
