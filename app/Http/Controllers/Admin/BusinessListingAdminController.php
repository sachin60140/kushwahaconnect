<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BusinessListing;

class BusinessListingAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = BusinessListing::with(['user', 'state', 'district'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', '%' . $request->search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($request) {
                        $userQuery->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        return Inertia::render('Admin/Business/Index', [
            'businesses' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $business = BusinessListing::findOrFail($id);
        $business->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'Business listing status updated successfully.');
    }

    public function destroy($id)
    {
        $business = BusinessListing::findOrFail($id);
        
        if ($business->logo_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($business->logo_path);
        }
        
        if ($business->cover_image_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($business->cover_image_path);
        }
        
        $business->delete();

        return redirect()->back()->with('success', 'Business listing deleted successfully.');
    }
}
