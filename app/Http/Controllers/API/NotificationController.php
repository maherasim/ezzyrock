<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Resources\API\NotificationResource;

class NotificationController extends Controller
{
    public function notificationList(Request $request)
    {
        $user = auth()->user();

        $user->last_notification_seen = now();
        $user->save();
        date_default_timezone_set(getTimeZone());
        $type = isset($request->type) ? $request->type : null;
        if($type == "markas_read"){
            if(count($user->unreadNotifications) > 0 ) {
                $user->unreadNotifications->markAsRead();
            }
        }

        $limit = $request->filled('per_page') ? (int) $request->per_page : 100;
        $limit = max(1, $limit);
        $notifications = $user->notifications()->latest()->paginate($limit);

        $all_unread_count = isset($user->unreadNotifications) ? $user->unreadNotifications->count() : 0;

        $items = NotificationResource::collection($notifications);
        $response = [
            'notification_data' => $items,
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
            ],
            'all_unread_count' => $all_unread_count,
        ];
        return comman_custom_response($response);
    }

}
