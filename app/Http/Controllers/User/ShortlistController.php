<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Shortlist;
use Illuminate\Http\Request;

class ShortlistController extends Controller
{
    public function destroy(Request $request, int $id)
    {
        Shortlist::where('user_id', $request->user()->id)
            ->where('matrimonial_profile_id', $id)
            ->delete();

        return back()->with('status', 'Profile removed from your shortlist.');
    }
}
