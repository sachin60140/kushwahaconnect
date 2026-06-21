<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConnectionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get received requests that are pending
        $receivedRequests = Connection::where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->with('sender.memberProfile')
            ->get();

        // Get sent requests that are pending
        $sentRequests = Connection::where('sender_id', $user->id)
            ->where('status', 'pending')
            ->with('receiver.memberProfile')
            ->get();

        // Get active connections
        $activeConnections = Connection::where(function($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
            })
            ->where('status', 'accepted')
            ->with(['sender.memberProfile', 'receiver.memberProfile'])
            ->get();

        return Inertia::render('User/Connections/Index', [
            'receivedRequests' => $receivedRequests,
            'sentRequests' => $sentRequests,
            'activeConnections' => $activeConnections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
        ]);

        $senderId = $request->user()->id;
        $receiverId = $request->receiver_id;

        if ($senderId == $receiverId) {
            return back()->with('error', 'You cannot send a connection request to yourself.');
        }

        // Check if connection already exists
        $existing = Connection::where(function($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $senderId)->where('receiver_id', $receiverId);
        })->orWhere(function($q) use ($senderId, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $senderId);
        })->first();

        if ($existing) {
            return back()->with('error', 'A connection or request already exists.');
        }

        Connection::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => 'pending',
        ]);

        return back()->with('status', 'Connection request sent successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $connection = Connection::findOrFail($id);

        if ($connection->receiver_id !== $request->user()->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        $connection->status = $request->status;
        $connection->save();

        $message = $request->status === 'accepted' ? 'Connection accepted.' : 'Connection rejected.';
        return back()->with('status', $message);
    }

    public function destroy(Request $request, $id)
    {
        $connection = Connection::findOrFail($id);

        if ($connection->sender_id !== $request->user()->id && $connection->receiver_id !== $request->user()->id) {
            return back()->with('error', 'Unauthorized action.');
        }

        $connection->delete();

        return back()->with('status', 'Connection removed.');
    }
}
