<?php

namespace App\Jobs;

use App\Events\EmailTransactionStatusUpdated;
use App\Mail\SendEmailTest;
use App\Mail\SendWeb;
use App\Models\EmailTransaction;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailTransaction;
    protected $emailInfo;
    protected $sender;
    protected $page;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EmailTransaction $emailTransaction, $emailInfo, $sender)
    {
        $this->sender = $sender;
        $this->emailTransaction = $emailTransaction;
        $this->emailInfo = $emailInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $customerEmail = $this->emailTransaction->invoice->customer->email;
        $file = $this->emailTransaction->invoice->media()->first()->file_url;
        try {
            $email = null;
            if ($this->emailTransaction->method == 'mail') {
                $email = new SendEmailTest(["email" => $customerEmail, "subject" => $this->emailInfo["subject"], "message" => $this->emailInfo["message"], "file" => $file]);
            } else {
                $email = new SendWeb($this->emailTransaction);
            }
            Mail::to($customerEmail)->send($email);

            // Update the email transaction status to 'sent'
            $this->emailTransaction->status = 'sent';
            $this->emailTransaction->error_message = null;
            $this->sender->sendEmailNotification($this->emailTransaction);
            $this->emailTransaction->save();
            // Retrieve email transactions for the current sender
            if ($this->emailTransaction->send_method == 'mail')
                $this->deleteDownloadedFile($file);


            // Broadcast the list update event
            event(new EmailTransactionStatusUpdated($this->sender, $this->emailTransaction->toArray()));
        } catch (Exception $e) {
            // Update the email transaction status to 'failed' and save the error message
            $this->emailTransaction->status = 'failed';
            $this->emailTransaction->error_message = $e->getMessage();
            $this->emailTransaction->save();
            event(new EmailTransactionStatusUpdated($this->sender, $this->emailTransaction->toArray()));
            if ($this->emailTransaction->method == 'mail')
                $this->deleteDownloadedFile($file);

        }
    }

    private function deleteDownloadedFile($fileUrl)
    {
        // Extract the file name from the file URL
        $fileName = basename($fileUrl);

        // Delete the file from the "temporary" disk if it exists
        if (Storage::disk('temporary')->exists($fileName)) {
            Storage::disk('temporary')->delete($fileName);
        }
    }
}
