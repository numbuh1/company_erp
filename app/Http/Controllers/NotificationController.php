<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * All-notifications page — marks everything as read on load.
     */
    public function index()
    {
        $user = auth()->user();

        // Mark all unread as read when user views the page
        $user->unreadNotifications()->update(['read_at' => now()]);

        $notifications = $user->notifications()->latest()->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * AJAX endpoint: mark all unread as read.
     */
    public function markRead(Request $request)
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
