<?php

namespace App\Jobs;

use App\Events\EmailTransactionStatusUpdated;
use App\Mail\SendEmailTest;
use App\Models\EmailTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $emailTransaction;
    protected $filePath;
    protected $page;
    protected $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(EmailTransaction $emailTransaction, $filePath, $page, $user)
    {
        $this->user = $user;
        $this->emailTransaction = $emailTransaction;
        $this->filePath = $filePath;
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
            $email = new SendEmailTest(["email" => $customerEmail, "filePath" => $this->filePath]);
            Mail::to($customerEmail)->send($email);

            // Update the email transaction status to 'sent'
            $this->emailTransaction->status = 'sent';
            $this->emailTransaction->save();
            $user = $this->user;
            // Retrieve email transactions for the current user
            $emailTransactions = EmailTransaction::whereHas('invoice', function ($query) use ($user) {
                $query->where('sender_id', $user->id);
            })->select('id', 'status')->simplePaginate(10, ['*'], 'page', $this->page);

            // Broadcast the list update event
            event(new EmailTransactionStatusUpdated($emailTransactions));
        } catch (\Exception $e) {
            // Update the email transaction status to 'failed' and save the error message
            $this->emailTransaction->status = 'failed';
            $this->emailTransaction->error_message = $e->getMessage();
            $this->emailTransaction->save();
        }

        Storage::disk('temporary')->delete($this->filePath);
    }
}
