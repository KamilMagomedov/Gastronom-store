<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->get()
            ->map(function ($notification) {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'type' => $data['type'] ?? 'news',
                    'event' => $data['event'] ?? null,
                    'order_id' => $data['order_id'] ?? null,
                    'title' => $data['title'] ?? '',
                    'message' => $data['message'] ?? '',
                    'status' => $data['status'] ?? null,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            })
            ->values()
            ->all();

        return ApiResponse::success([
            'notifications' => $notifications,
            'unread_count' => $request->user()
                ->unreadNotifications()
                ->count(),
        ]);
    }

    public function markAsRead(
        Request $request,
        string $notification
    ) {
        $notificationModel = $request->user()
            ->notifications()
            ->where('id', $notification)
            ->firstOrFail();

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        return ApiResponse::success([
            'id' => $notificationModel->id,
            'read_at' => $notificationModel->fresh()->read_at?->toISOString(),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()
            ->unreadNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return ApiResponse::success([
            'unread_count' => 0,
        ]);
    }
}