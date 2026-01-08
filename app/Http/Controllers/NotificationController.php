<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);
        return response()->json($notifications);
    }

    public function unread()
    {
        $notifications = auth()->user()->unreadNotifications()->get();
        return response()->json($notifications);
    }

    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->update(['read' => true]);
        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update(['read' => true]);
        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function count()
    {
        $count = auth()->user()->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }
}
