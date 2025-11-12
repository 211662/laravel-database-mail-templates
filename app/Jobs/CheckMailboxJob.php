<?php

namespace App\Jobs;

use App\Models\TempEmail;
use App\Services\MailReceiver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckMailboxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tempEmail;

    /**
     * Create a new job instance.
     */
    public function __construct(TempEmail $tempEmail)
    {
        $this->tempEmail = $tempEmail;
    }

    /**
     * Execute the job.
     */
    public function handle(MailReceiver $receiver)
    {
        try {
            // Only check if email is still active
            if ($this->tempEmail->isExpired()) {
                return;
            }

            $receiver->fetchNewMails($this->tempEmail);
            
            \Log::info('Checked mailbox for ' . $this->tempEmail->email);
        } catch (\Exception $e) {
            \Log::error('Failed to check mailbox', [
                'email' => $this->tempEmail->email,
                'error' => $e->getMessage(),
            ]);
            
            // Retry up to 3 times
            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }
}
