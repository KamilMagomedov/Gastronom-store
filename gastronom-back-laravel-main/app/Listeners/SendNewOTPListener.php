<?php

namespace App\Listeners;

use App\Events\ResendOTPCreated;
use App\Notifications\SendOTPVerificationNotification;

class SendNewOTPListener
{
    /**
     * Handle the event.
     */
    public function handle(ResendOTPCreated $event): void
    {
        $event->customer
            ->notify(
                new SendOTPVerificationNotification($event->token, $event->customer->id)
            );
    }
}
