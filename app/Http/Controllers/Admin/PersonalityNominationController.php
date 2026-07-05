<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonalityNomination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PersonalityNominationController extends Controller
{
    public function index(Request $request)
    {
        $query = PersonalityNomination::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('person_name', 'like', "%{$search}%")
                    ->orWhere('nominator_name', 'like', "%{$search}%")
                    ->orWhere('profession_or_designation', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/Nominations/Index', [
            'nominations' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
            'statuses' => PersonalityNomination::STATUSES,
        ]);
    }

    public function update(Request $request, PersonalityNomination $nomination)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PersonalityNomination::STATUSES))],
            'admin_note' => 'nullable|string|max:5000',
        ]);

        $nomination->update($validated);

        return redirect()->back()->with('success', 'Nomination status updated.');
    }

    public function destroy(PersonalityNomination $nomination)
    {
        if ($nomination->photograph_path) {
            Storage::disk('public')->delete($nomination->photograph_path);
        }

        if ($nomination->supporting_document_path) {
            Storage::disk('public')->delete($nomination->supporting_document_path);
        }

        $nomination->delete();

        return redirect()->back()->with('success', 'Nomination deleted.');
    }
}
