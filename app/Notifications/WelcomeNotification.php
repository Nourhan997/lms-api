<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Welcome to LMS!')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Welcome to the Learning Management System. Your account has been created successfully.')
            ->line('Start exploring courses and begin your learning journey today.')
            ->action('Browse Courses', config('app.url'))
            ->line('Thank you for joining us!');
    }
}
