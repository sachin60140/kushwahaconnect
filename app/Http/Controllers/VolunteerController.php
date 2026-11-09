<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Volunteer;
use Inertia\Inertia;

class VolunteerController extends Controller
{
    public function index(Request $request)
    {
        $query = Volunteer::where('status', 'Approved');

        if ($request->filled('state')) {
            $query->where('state', 'like', '%' . $request->state . '%');
        }

        if ($request->filled('district')) {
            $query->where('district', 'like', '%' . $request->district . '%');
        }

        if ($request->filled('help_area')) {
            $query->whereJsonContains('help_areas', $request->help_area);
        }

        return Inertia::render('Volunteer/Index', [
            'volunteers' => $query->latest()->paginate(12)->withQueryString(),
            'filters' => $request->only(['state', 'district', 'help_area'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Volunteer/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'state' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'skills' => 'nullable|string|max:255',
            'available_time' => 'nullable|string|max:255',
            'help_areas' => 'nullable|array'
        ]);

        if ($request->user()) {
            $validated['user_id'] = $request->user()->id;
        }

        Volunteer::create($validated);

        return redirect()->back()->with('success', 'Thank you for registering as a volunteer! Your application is pending admin approval.');
    }
}
