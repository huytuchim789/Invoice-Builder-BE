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

    public $emailTransaction;

    public function __construct($emailTransaction)
    {
        $this->emailTransaction = $emailTransaction;
    }

    public function broadcastOn()
    {
        return new Channel('email-transactions');
    }

    public function broadcastAs()
    {
        return 'list-updated';
    }
}
