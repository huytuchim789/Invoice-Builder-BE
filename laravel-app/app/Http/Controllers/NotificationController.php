<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\EmailTransaction;
use App\Models\User;
use App\Notifications\SendEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Get all notifications for the authenticated user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 0) + 1;

            $notifications = $request->user()
                ->notifications()
                ->latest('updated_at') // Order by updated_at in descending order
                ->latest('created_at') // Order by created_at in descending order
                ->paginate($limit, ['*'], 'page', $page);

            // Retrieve the email_transaction_id from each notification and fetch the EmailTransaction
            $notifications->getCollection()->transform(function ($notification) {
               if($notification->type==SendEmail::class){
                   $data = $notification->data;
                   $emailTransactionId = $data['email_transaction_id'] ?? null;
                   $emailTransaction = $emailTransactionId ? EmailTransaction::with('invoice')->find($emailTransactionId) : null;
                   $data['email_transaction'] = $emailTransaction;
                   unset($data['email_transaction_id']);

                   $senderId = $notification->notifiable_id;
                   $sender = $senderId ? User::find($senderId) : null;
                   $data['sender'] = $sender;

                   $notification->data = $data;
                   return $notification;
               }
               else{
                   $data = $notification->data;
                   $commentId = $data['comment_id'] ?? null;
                   $comment = $commentId ? Comment::with('pin.invoice')->find($commentId) : null;
                   $data['comment'] = $comment;
                   unset($data['comment_id']);

                   $notification->data = $data;
                   return $notification;
               }
            });

            return Response::customJson(200, ['notifications' => $notifications], "Success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }


    /**
     * Mark a single notification as read.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        try {
            $notification = Auth::user()->notifications()->findOrFail($id);
            $notification->markAsRead();

            return Response::customJson(200, ['message' => 'Notification marked as read'], "Success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        try {
            $request->user()->unreadNotifications->markAsRead();

            return Response::customJson(200, ['message' => 'All notifications marked as read'], "Success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Get all unread notifications for the authenticated user.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function unread(Request $request)
    {
        try {
            $unreadNotifications = $request->user()->unreadNotifications;

            return Response::customJson(200, ['unread_notifications' => $unreadNotifications], "Success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
}
