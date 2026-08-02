<?php

namespace App\Listeners;

use App\Events\CustomerCreated;
use App\Notifications\SendOTPVerificationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendCustomerOTPVerificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CustomerCreated $event): void
    {
        $event->customer->notify(
            new SendOTPVerificationNotification($event->token, $event->customer->id)
        );
    }

    public function failed(CustomerCreated $event, \Throwable $exception): void
    {
        \Log::error('Failed to send OTP', [
            'customer_id' => $event->customer->id,
            'token' => $event->token,
            'error' => $exception->getMessage(),
        ]);
    }
}
