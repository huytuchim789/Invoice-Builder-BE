<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailTransactionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $emailTransaction;
    protected $sender;

    public function __construct($sender, $emailTransaction, )
    {
        $this->emailTransaction = $emailTransaction;
        $this->sender = $sender;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('sender=' . $this->sender->id . '_email-transactions');
    }

    public function broadcastAs()
    {
        return 'list-updated';
    }

    public function broadcastWith()
    {
        return [
            'emailTransaction' => $this->emailTransaction,
        ];
    }
}
