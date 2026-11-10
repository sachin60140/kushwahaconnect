<?php

namespace App\Http\Controllers;

use App\Support\ImageUpload;
use Illuminate\Http\Request;
use App\Models\NewsEvent;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class AdminNewsController extends Controller
{
    public function index()
    {
        $items = NewsEvent::latest()->paginate(15)->through(function ($item) {
            if ($item->image) {
                $item->image_url = asset('storage/' . $item->image);
            }
            return $item;
        });

        return Inertia::render('Admin/News/Index', [
            'items' => $items
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/News/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,event,success_story,update',
            'event_date' => 'nullable|required_if:type,event|date',
            'event_location' => 'nullable|required_if:type,event|string|max:255',
            'registration_url' => 'nullable|url|max:255',
            'image' => 'nullable|image|max:2048',
            'is_published' => 'boolean'
        ]);

        if ($validated['type'] !== 'event') {
            $validated['event_date'] = null;
            $validated['event_location'] = null;
            $validated['registration_url'] = null;
        }

        $validated['event_date'] = self::eventDateToUtc($validated['event_date'] ?? null);

        if ($request->hasFile('image')) {
            $validated['image'] = ImageUpload::storeCover($request->file('image'), 'news_events');
        }

        NewsEvent::create($validated);

        return redirect()->route('admin.news.index')->with('success', 'Item created successfully.');
    }

    public function edit($id)
    {
        $item = NewsEvent::findOrFail($id);
        if ($item->image) {
            $item->image_url = asset('storage/' . $item->image);
        }
        // Prefill the datetime-local input in India time.
        $item->event_date_local = $item->event_date
            ?->copy()->setTimezone(self::displayZone())->format('Y-m-d\TH:i');

        return Inertia::render('Admin/News/Edit', [
            'item' => $item
        ]);
    }

    private static function displayZone(): string
    {
        return config('app.display_timezone', 'Asia/Kolkata');
    }

    /** Admins type the event time in India time; store the real moment in UTC. */
    private static function eventDateToUtc(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value, self::displayZone())->utc() : null;
    }

    public function update(Request $request, $id)
    {
        $item = NewsEvent::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'type' => 'required|in:news,event,success_story,update',
            'event_date' => 'nullable|required_if:type,event|date',
            'event_location' => 'nullable|required_if:type,event|string|max:255',
            'registration_url' => 'nullable|url|max:255',
            'image' => 'nullable|image|max:2048',
            'is_published' => 'boolean'
        ]);

        if ($validated['type'] !== 'event') {
            $validated['event_date'] = null;
            $validated['event_location'] = null;
            $validated['registration_url'] = null;
        }

        $validated['event_date'] = self::eventDateToUtc($validated['event_date'] ?? null);

        if ($request->hasFile('image')) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
            $validated['image'] = ImageUpload::storeCover($request->file('image'), 'news_events');
        }

        $item->update($validated);

        return redirect()->route('admin.news.index')->with('success', 'Item updated successfully.');
    }

    public function destroy($id)
    {
        $item = NewsEvent::findOrFail($id);
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }
        $item->delete();

        return redirect()->route('admin.news.index')->with('success', 'Item deleted successfully.');
    }
}
