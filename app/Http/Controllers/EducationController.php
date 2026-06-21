<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EducationResource;
use Inertia\Inertia;

class EducationController extends Controller
{
    public function index(Request $request)
    {
        $query = EducationResource::where('is_published', true);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return Inertia::render('Education/Index', [
            'resources' => $query->latest()->paginate(12),
            'filters' => $request->only(['category'])
        ]);
    }
}
