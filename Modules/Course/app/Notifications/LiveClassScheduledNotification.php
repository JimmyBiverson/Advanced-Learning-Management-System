<?php

namespace Modules\Course\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Course\Models\CourseLiveClass;

class LiveClassScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly CourseLiveClass $liveClass)
    {
        $this->liveClass->loadMissing('course.instructor.user');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->isCourseInstructor($notifiable)) {
            return (new MailMessage)
                ->subject('You have a live class to handle: '.$this->liveClass->class_topic)
                ->greeting('Hello '.$notifiable->name.',')
                ->line('A live class has been scheduled that you are responsible for teaching.')
                ->line('Topic: '.$this->liveClass->class_topic)
                ->line('Starts: '.$this->liveClass->class_date_and_time->toDayDateTimeString())
                ->line('Remember to start the class on time so your students can join.')
                ->action('Open live class', route('live-class.start', $this->liveClass->id));
        }

        return (new MailMessage)
            ->subject('Live class scheduled: '.$this->liveClass->class_topic)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A live class has been scheduled for your course.')
            ->line('Topic: '.$this->liveClass->class_topic)
            ->line('Starts: '.$this->liveClass->class_date_and_time->toDayDateTimeString())
            ->action('Open live class', route('live-class.start', $this->liveClass->id));
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Live class scheduled',
            'body' => $this->liveClass->class_topic.' is scheduled for '.$this->liveClass->class_date_and_time->toDayDateTimeString().'.',
            'url' => route('live-class.start', $this->liveClass->id),
        ];
    }

    private function isCourseInstructor(object $notifiable): bool
    {
        if (! $notifiable instanceof User) {
            return false;
        }

        $instructorUser = $this->liveClass->course?->instructor?->user;

        return $instructorUser !== null && $instructorUser->id === $notifiable->id;
    }
}