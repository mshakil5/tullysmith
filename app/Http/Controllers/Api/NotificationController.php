<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    public function markRead(Request $request, $id)
    {
        Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['message' => 'Marked as read.']);
    }

    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);

        return response()->json(['message' => 'All marked as read.']);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string|max:4096']);
        $request->user()->update(['fcm_token' => $request->fcm_token, 'fcm_registered_at' => now()]);
        return response()->json(['message' => 'Token saved.']);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Logged out.']);
    }

    public function deleteFcmToken(Request $request)
    {
        $request->user()->update(['fcm_token' => null, 'fcm_registered_at' => null]);
        return response()->json(['message' => 'Token removed.']);
    }
}