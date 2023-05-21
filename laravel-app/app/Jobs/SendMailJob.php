<?php

namespace App\Jobs;

use App\Mail\SendEmailTest;
use App\Models\EmailTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
    protected $filePath;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EmailTransaction $emailTransaction, $filePath)
    {
        $this->emailTransaction = $emailTransaction;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()

    {
        // $email = new SendEmailTest($this->data);

        try {
            $customerEmail = $this->emailTransaction->customer->email;
            $email = new SendEmailTest(["email" => $customerEmail, "filePath" => $this->filePath]);
            Mail::to($customerEmail)->send($email);

            // Update the email transaction status to 'sent'
            $this->emailTransaction->status = 'sent';
            $this->emailTransaction->save();
        } catch (\Exception $e) {
            // Update the email transaction status to 'failed' and save the error message
            $this->emailTransaction->status = 'failed';
            $this->emailTransaction->error_message = $e->getMessage();
            $this->emailTransaction->save();
        }

        Storage::disk('temporary')->delete($this->filePath);
    }
}
