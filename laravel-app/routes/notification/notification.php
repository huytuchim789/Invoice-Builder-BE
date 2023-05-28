<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->group(function () {
    // Get all notifications
    Route::get('/', [NotificationController::class, 'index']);

    // Mark a notification as read
    Route::put('{id}/mark-as-read', [NotificationController::class, 'markAsRead']);

    // Mark all notifications as read
    Route::put('mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // Get all unread notifications
    Route::get('unread', [NotificationController::class, 'unread']);
});
