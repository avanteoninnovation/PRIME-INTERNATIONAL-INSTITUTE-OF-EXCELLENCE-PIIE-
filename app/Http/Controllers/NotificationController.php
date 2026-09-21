<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One controller behind every role's "My Notifications" page and bell
 * dropdown (student/teacher/parent/admin, see routes/web.php) — every
 * method here is scoped purely by auth()->user()->id, never by role, since
 * a notification inbox is a per-user concept, not a per-role one. Each role
 * gets its own route prefix (student/notifications, teacher/notifications,
 * ...) only so the existing per-role middleware groups and navigation
 * layouts keep working the way every other module in this app already
 * does — the behavior underneath is identical for all of them.
 */
class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $notifications = UserNotification::forUser($user->id)
            ->latest('id')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    /** AJAX partial for the bell dropdown: the most recent few + unread count. */
    public function dropdown()
    {
        $user = Auth::user();

        $notifications = UserNotification::forUser($user->id)
            ->latest('id')
            ->take(8)
            ->get();

        $unreadCount = UserNotification::forUser($user->id)->unread()->count();

        return view('notifications._dropdown', compact('notifications', 'unreadCount'));
    }

    public function unreadCount()
    {
        $user = Auth::user();

        return response()->json([
            'count' => UserNotification::forUser($user->id)->unread()->count(),
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $user = Auth::user();

        $notification = UserNotification::forUser($user->id)->findOrFail((int) $id);
        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        if ($notification->url) {
            return redirect()->to($notification->url);
        }

        return redirect()->back();
    }

    public function markAllRead()
    {
        $user = Auth::user();

        UserNotification::forUser($user->id)->unread()->update(['read_at' => now()]);

        return redirect()->back()->with('message', get_phrase('All notifications marked as read.'));
    }
}
