<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SendWeb extends Mailable
{
    use Queueable, SerializesModels;

    protected $emailTransaction;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($emailTransaction)
    {
        $this->emailTransaction = $emailTransaction;
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */


    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.send_web',
            with: [
                'link' => env('FRONTEND_URL') . '/invoice/review/' . $this->emailTransaction->invoice->id
            ],
        );
    }

}
