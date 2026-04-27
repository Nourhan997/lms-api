<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceiptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Payment $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Payment Receipt: {$this->payment->course->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Your payment has been confirmed.")
            ->line("Course: **{$this->payment->course->title}**")
            ->line("Amount: {$this->payment->amount} {$this->payment->currency}")
            ->line("Date: " . $this->payment->paid_at->format('Y-m-d H:i'))
            ->line("Reference: {$this->payment->id}")
            ->action('Start Learning', config('app.url'))
            ->line('Thank you for your purchase!');
    }
}
