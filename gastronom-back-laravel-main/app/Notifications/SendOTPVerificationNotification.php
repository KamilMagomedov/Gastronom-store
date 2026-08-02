<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOTPVerificationNotification extends Notification implements ShouldQueueAfterCommit
{
    use InteractsWithQueue, Queueable;

    public function __construct(public string $token, public int $customerId)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Verification Code')
            ->line('Verification code:')
            ->line($this->token)
            ->line('Please, enter this code in application to complete authorization');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Send to customer OTP notification failed', [
            'customer_id' => $this->customerId,
            'token' => $this->token,
        ]);
    }
}
