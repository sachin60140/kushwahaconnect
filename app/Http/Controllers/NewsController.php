<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsEvent;
use Inertia\Inertia;

class NewsController extends Controller
{
    public function index()
    {
        $items = NewsEvent::where('is_published', true)
            ->latest()
            ->paginate(10)
            ->through(function ($item) {
                if ($item->image) {
                    $item->image_url = asset('storage/' . $item->image);
                }
                $item->type_label = str_replace('_', ' ', $item->type);
                return $item;
            });

        return Inertia::render('NewsIndex', [
            'items' => $items
        ]);
    }
}
