<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EducationResource;
use Inertia\Inertia;

class AdminEducationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Education/Index', [
            'resources' => EducationResource::latest()->paginate(15)
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Education/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:Scholarship,Course,Mentorship,General',
            'description' => 'required|string',
            'link_url' => 'nullable|url|max:255',
            'deadline_date' => 'nullable|date',
            'is_published' => 'boolean'
        ]);

        EducationResource::create($validated);

        return redirect()->route('admin.education.index')->with('success', 'Resource created successfully.');
    }

    public function edit($id)
    {
        return Inertia::render('Admin/Education/Edit', [
            'resource' => EducationResource::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $resource = EducationResource::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:Scholarship,Course,Mentorship,General',
            'description' => 'required|string',
            'link_url' => 'nullable|url|max:255',
            'deadline_date' => 'nullable|date',
            'is_published' => 'boolean'
        ]);

        $resource->update($validated);

        return redirect()->route('admin.education.index')->with('success', 'Resource updated successfully.');
    }

    public function destroy($id)
    {
        EducationResource::findOrFail($id)->delete();
        return redirect()->route('admin.education.index')->with('success', 'Resource deleted successfully.');
    }
}
