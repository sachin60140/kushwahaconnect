<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalResource;
use Inertia\Inertia;

class MedicalController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicalResource::where('is_published', true);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return Inertia::render('Medical/Index', [
            'resources' => $query->latest()->paginate(12),
            'filters' => $request->only(['type'])
        ]);
    }
}
