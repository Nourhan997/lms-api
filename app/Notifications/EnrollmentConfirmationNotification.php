<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Course $course
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject("Enrollment Confirmed: {$this->course->title}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have successfully enrolled in **{$this->course->title}**.")
            ->line('Enrolled on: ' . now()->format('Y-m-d'))
            ->action('Start Learning', config('app.url'))
            ->line('Happy learning!');
    }
}
