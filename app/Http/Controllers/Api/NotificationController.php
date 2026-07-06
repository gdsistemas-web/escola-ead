<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return Notification::query()
            ->where('user_id', $request->user()->id)
            ->when($request->type, fn ($query, $type) => $query->where('type', 'like', "{$type}%"))
            ->when($request->unread, fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate((int) $request->integer('per_page', 30));
    }

    public function unreadCount(Request $request)
    {
        return [
            'unread' => Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
        ];
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return $notification;
    }

    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->noContent();
    }
}
