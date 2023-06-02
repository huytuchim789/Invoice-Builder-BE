<?php

namespace App\Jobs;

use App\Events\EmailTransactionStatusUpdated;
use App\Mail\SendEmailTest;
use App\Models\EmailTransaction;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
    public function __construct(EmailTransaction $emailTransaction, $emailInfo, $sender, $page)
    {
        $this->sender = $sender;
        $this->emailTransaction = $emailTransaction;
        $this->emailInfo = $emailInfo;
        $this->page = $page;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $customerEmail = $this->emailTransaction->invoice->customer->email;
            $file = $this->emailTransaction->invoice->media()->first()->file_url;
            $email = new SendEmailTest(["email" => $customerEmail, "subject" => $this->emailInfo["subject"], "message" => $this->emailInfo["message"], "file" => $file]);
            Mail::to($customerEmail)->send($email);

            // Update the email transaction status to 'sent'
            $this->emailTransaction->status = 'sent';
            $this->emailTransaction->error_message = null;
            $this->sender->sendEmailNotification($this->emailTransaction);
            $this->emailTransaction->save();
            // Retrieve email transactions for the current sender


            // Broadcast the list update event
            event(new EmailTransactionStatusUpdated($this->sender, $this->emailTransaction->toArray(), $this->page));
        } catch (Exception $e) {
            // Update the email transaction status to 'failed' and save the error message
            $this->emailTransaction->status = 'failed';
            $this->emailTransaction->error_message = $e->getMessage();
            $this->emailTransaction->save();
            event(new EmailTransactionStatusUpdated($this->sender, $this->emailTransaction->toArray(), $this->page));
        }
    }
}
