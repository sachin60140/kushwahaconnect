<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::where('is_published', true)->with('images')->latest()->get();

        return Inertia::render('Gallery/Index', [
            'galleries' => $galleries
        ]);
    }

    public function show($id)
    {
        $gallery = Gallery::where('is_published', true)->with('images')->findOrFail($id);

        return Inertia::render('Gallery/Show', [
            'gallery' => $gallery
        ]);
    }
}
