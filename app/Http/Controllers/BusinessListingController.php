<?php

namespace App\Http\Controllers;

use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BusinessListing;
use App\Models\State;

class BusinessListingController extends Controller
{
    // List of categories to be used across the controller and views
    public static $categories = [
        'Agriculture & Farming',
        'Arts & Entertainment',
        'Beauty & Personal Care',
        'Construction & Real Estate',
        'Education & Training',
        'Finance & Insurance',
        'Food & Beverage',
        'Healthcare & Medical',
        'IT & Technology',
        'Manufacturing',
        'Media & Communications',
        'Professional Services',
        'Retail & Wholesale',
        'Transportation & Logistics',
        'Other'
    ];

    public function index(Request $request)
    {
        $query = BusinessListing::with(['state', 'district'])
            ->where('status', 'approved');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('business_name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('state_id')) {
            $query->where('state_id', $request->state_id);
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->district_id);
        }

        return Inertia::render('Business/Index', [
            'businesses' => $query->latest()->paginate(12),
            'states' => State::where('status', true)->get(),
            'categories' => self::$categories,
            'filters' => $request->only(['search', 'category', 'state_id', 'district_id'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Business/Create', [
            'states' => State::where('status', true)->get(),
            'categories' => self::$categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string|max:255',
            'state_id' => 'required|exists:states,id',
            'district_id' => 'required|exists:districts,id',
            'pincode' => 'required|string|max:10',
            'contact_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096'
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo_path'] = ImageUpload::storeSquare($request->file('logo'), 'business_logos');
        }
        
        if ($request->hasFile('cover_image')) {
            $validated['cover_image_path'] = ImageUpload::storeCover($request->file('cover_image'), 'business_covers');
        }

        unset($validated['logo']);
        unset($validated['cover_image']);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'pending';

        BusinessListing::create($validated);

        return redirect()->route('businesses.index')
            ->with('success', 'Your business listing has been submitted successfully and is pending admin approval.');
    }
}
