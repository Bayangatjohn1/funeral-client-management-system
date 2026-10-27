<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Password;

class SendAccountResetLink implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public string $email) {}

    public function handle(): void
    {
        // Create the token only in the worker: reset secrets never enter the queue payload.
        try {
            Password::sendResetLink(['email' => $this->email]);
        } catch (\Throwable) {
            // SMTP exceptions can contain message contents. Keep failed-job diagnostics secret-free.
            throw new \RuntimeException('Password recovery delivery failed. Check mail configuration and provider availability.');
        }
    }
}
