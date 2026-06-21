<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminGalleryController extends Controller
{
    public function index()
    {
        $galleries = Gallery::withCount('images')->latest()->paginate(10);
        return Inertia::render('Admin/Gallery/Index', [
            'galleries' => $galleries
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Gallery/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_published' => 'boolean',
            'images.*' => 'image|max:5120' // Max 5MB per image
        ]);

        $gallery = Gallery::create([
            'title' => $request->title,
            'description' => $request->description,
            'is_published' => $request->is_published ?? true
        ]);

        if ($request->hasFile('images')) {
            $isFirst = true;
            foreach ($request->file('images') as $image) {
                $path = $image->store('gallery', 'public');
                
                GalleryImage::create([
                    'gallery_id' => $gallery->id,
                    'image_path' => $path
                ]);

                // Set first image as thumbnail
                if ($isFirst) {
                    $gallery->update(['thumbnail' => $path]);
                    $isFirst = false;
                }
            }
        }

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery created successfully.');
    }

    public function destroy($id)
    {
        $gallery = Gallery::findOrFail($id);
        
        // Delete images from storage
        foreach ($gallery->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        if ($gallery->thumbnail) {
            // Usually the thumbnail is just one of the images, but just in case
            Storage::disk('public')->delete($gallery->thumbnail);
        }

        $gallery->delete();

        return redirect()->route('admin.gallery.index')->with('success', 'Gallery deleted successfully.');
    }
}
