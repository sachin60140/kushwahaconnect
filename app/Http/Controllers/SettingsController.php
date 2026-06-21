<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebsiteSetting;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = WebsiteSetting::pluck('value', 'key')->toArray();

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'founder_photo_upload' => 'nullable|image|max:4096',
        ]);

        $data = $request->except(['_token', 'founder_photo_upload']);

        if ($request->hasFile('founder_photo_upload')) {
            $previousPhoto = WebsiteSetting::where('key', 'founder_photo_url')->value('value');
            $path = $request->file('founder_photo_upload')->store('founder', 'public');

            if ($previousPhoto && Str::startsWith($previousPhoto, ['/storage/founder/', asset('storage/founder')])) {
                $previousPath = Str::after($previousPhoto, '/storage/');
                Storage::disk('public')->delete($previousPath);
            }

            $data['founder_photo_url'] = Storage::disk('public')->url($path);
        }

        foreach ($data as $key => $value) {
            WebsiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ?? '']
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}
