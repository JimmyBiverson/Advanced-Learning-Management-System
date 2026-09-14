<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $name, private readonly string $email) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New user registered')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->name.' ('.$this->email.') created a student account.')
            ->action('View users', route('users.index'));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Student Registered',
            'body' => $this->name.' ('.$this->email.') created a student account.',
            'url' => route('users.index'),
        ];
    }
}
