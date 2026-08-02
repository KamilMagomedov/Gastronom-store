<?php

namespace App\Listeners;

use App\Events\PasswordResetOtpGenerated;
use App\Notifications\SendPasswordResetOtpGeneratedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class PasswordResetOtpGeneratedListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PasswordResetOtpGenerated $event): void
    {
        $event->customer->notify(
            new SendPasswordResetOtpGeneratedNotification($event->token)
        );
    }
}
