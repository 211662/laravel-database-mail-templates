<?php

namespace App\Jobs;

use App\Models\TempEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupExpiredEmailsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        $expiredEmails = TempEmail::expired()
            ->where('created_at', '<', now()->subDays(7)) // Delete emails older than 7 days
            ->get();

        $count = 0;
        foreach ($expiredEmails as $email) {
            $email->delete();
            $count++;
        }

        \Log::info("Cleaned up {$count} expired temp emails");
    }
}
