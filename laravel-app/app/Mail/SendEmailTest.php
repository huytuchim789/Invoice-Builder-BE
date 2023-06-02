<?php

namespace App\Mail;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
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

        $fileUrl = $this->data["file"];
        if (!$fileUrl) {
            return Response::customJson(404, null, "File does not exist in the system");
        }

        $fileName = basename($fileUrl);
        $tempFilePath = storage_path('app'.DIRECTORY_SEPARATOR.'temporary'.DIRECTORY_SEPARATOR . $fileName);

        // Download the file from Cloudinary and save it locally
        Cloudinary::($fileUrl, $tempFilePath);

        // Check if the file was downloaded successfully
        if (!file_exists($tempFilePath)) {
            return Response::customJson(404, null, "File could not be downloaded from Cloudinary");
        }
//
//        // Create an UploadedFile instance from the downloaded file
//        $file = new UploadedFile($tempFilePath);
//
//        // Create the Attachment object
//        $attachment = Attachment::fromPath($file->getRealPath())
//            ->as($file->getClientOriginalName())
//            ->withMime($file->getClientMimeType());
//
//        // Return the attachment as an array
//        return [$attachment];
    }
}
