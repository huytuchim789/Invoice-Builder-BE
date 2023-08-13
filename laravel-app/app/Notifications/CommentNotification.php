<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CommentNotification extends Notification
{
    use Queueable;
    protected $comment;
    protected $sender;
    protected $invoiceId;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($comment,$sender,$invoiceId)
    {
        $this->comment = $comment;
        $this->sender = $sender;
        $this->invoiceId=$invoiceId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'invoice_id'=>$this->invoiceId??null,
            'comment_id' => $this->comment->id ?? null,
            'message' => 'commented '.$this->comment->content .' on your invoice',
            'sender'=>array_merge($this->sender->toArray(),['invoice_id'=>$this->invoiceId ?? null])
        ];
    }

    public function toBroadcast($notifiable)
    {
        $notification = DatabaseNotification::find($this->id);

        return new BroadcastMessage([
            'id' => $notification->id,
            'type' => get_class($this),
            'notifiable_id' => $notification->notifiable_id,
            'notifiable_type' => $notification->notifiable_type,
            'data' => array_merge($this->toArray($notifiable), ['sender' => $this->sender,'invoice_id'=>$this->invoiceId ?? null]),

            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
            'updated_at' => $notification->updated_at,
        ]);
    }

    /**
     * Get the sender data.
     *
     * @param  mixed  $notifiable
     * @return array|null
     */

}
