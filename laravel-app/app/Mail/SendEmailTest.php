<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SendEmailTest extends Mailable
{
    use Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->data["subject"],
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.test',
            with: [
                'emailMessage' => $this->data["message"],
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        $filePath = storage_path('app\temporary\\' . $this->data["filePath"]);

        if (!Storage::disk('temporary')->exists($this->data["filePath"])) {
            return  Response::customJson(404, null, "File not existed in system");
        }

        $file = new UploadedFile($filePath, basename($filePath));

        return [
            Attachment::fromPath($file->getRealPath())
                ->as($file->getClientOriginalName())
                ->withMime($file->getClientMimeType())
        ];
    }
}
