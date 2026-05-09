<?php

if (!function_exists('get_user_notifications')) {
    function get_user_notifications()
    {
        if (auth()->guard('web')->check()) {
            $user = auth("web")->user();
            $notifications = \App\Models\Notification::where("user_id", $user->id)
                ->orderBy('is_read', 'asc')  // Non lues en premier
                ->orderBy('created_at', 'desc')  // Ordre par date de création
                ->take(10)
                ->get();
            return $notifications;
        }
    }
}


if (!function_exists('get_user_unread_notifications_count')) {
    function get_user_unread_notifications_count()
    {
        if(auth()->guard('web')->check()){
            $user = auth("web")->user();
            $count = \App\Models\Notification::where("user_id", $user->id)->where("is_read", false)->count();
            return $count;
        }
        return 0;
    }
}
