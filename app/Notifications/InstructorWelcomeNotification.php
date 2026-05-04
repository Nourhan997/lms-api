<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InstructorWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Welcome to LMS — Instructor Account Created')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your instructor account has been created successfully.')
            ->line('You can now log in and start managing your courses.')
            ->action('Log In', config('app.url'))
            ->line('If you have any questions, please contact the administrator.');
    }
}
