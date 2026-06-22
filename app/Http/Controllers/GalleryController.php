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
        $previewImage = $gallery->thumbnail ?: $gallery->images->first()?->image_path;

        return Inertia::render('Gallery/Show', [
            'gallery' => $gallery,
            'seo' => [
                'title' => $gallery->title,
                'description' => $gallery->description ?: 'View photos and memories from Kushwaha community events on KushwahaConnect.',
                'image' => $previewImage ? asset('storage/'.$previewImage) : null,
                'image_alt' => $gallery->title.' photo gallery',
                'type' => 'article',
            ],
        ]);
    }
}
