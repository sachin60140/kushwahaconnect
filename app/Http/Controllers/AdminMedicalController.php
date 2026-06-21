<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalResource;
use Inertia\Inertia;

class AdminMedicalController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Medical/Index', [
            'resources' => MedicalResource::latest()->paginate(15)
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Medical/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:blood_request,doctor,hospital,other',
            'description' => 'required|string',
            'contact_info' => 'required|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'urgency' => 'nullable|string|max:255',
            'is_published' => 'boolean'
        ]);

        MedicalResource::create($validated);

        return redirect()->route('admin.medical.index')->with('success', 'Resource created successfully.');
    }

    public function edit($id)
    {
        return Inertia::render('Admin/Medical/Edit', [
            'resource' => MedicalResource::findOrFail($id)
        ]);
    }

    public function update(Request $request, $id)
    {
        $resource = MedicalResource::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:blood_request,doctor,hospital,other',
            'description' => 'required|string',
            'contact_info' => 'required|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'urgency' => 'nullable|string|max:255',
            'is_published' => 'boolean'
        ]);

        $resource->update($validated);

        return redirect()->route('admin.medical.index')->with('success', 'Resource updated successfully.');
    }

    public function destroy($id)
    {
        MedicalResource::findOrFail($id)->delete();
        return redirect()->route('admin.medical.index')->with('success', 'Resource deleted successfully.');
    }
}
