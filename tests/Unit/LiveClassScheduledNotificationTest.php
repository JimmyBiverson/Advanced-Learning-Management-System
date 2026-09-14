<?php

use App\Models\Instructor;
use App\Models\User;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseLiveClass;
use Modules\Course\Notifications\LiveClassScheduledNotification;
use Tests\TestCase;

uses(TestCase::class);

/**
 * Build a course with a fully preloaded relation chain so the
 * notification's loadMissing() call never touches the database.
 */
function notificationTestData(): array
{
    $instructorUser = new User(['name' => 'Dr. Host', 'email' => 'instructor@example.com']);
    $instructorUser->id = 22;

    $instructor = new Instructor(['id' => 8, 'user_id' => 22]);
    $instructor->setRelation('user', $instructorUser);

    $course = new Course(['instructor_id' => 8]);
    $course->setRelation('instructor', $instructor);

    return compact('instructorUser', 'instructor', 'course');
}

function notificationLiveClass(array $attributes = []): CourseLiveClass
{
    $liveClass = new CourseLiveClass(array_merge([
        'class_topic' => 'Math 101 Review',
        'class_date_and_time' => now()->addDay(),
        'course_id' => 1,
    ], $attributes));
    $liveClass->id = $attributes['id'] ?? 3;

    return $liveClass;
}

it('sends a host-oriented email to the course instructor', function () {
    ['instructorUser' => $instructorUser, 'course' => $course] = notificationTestData();

    $liveClass = notificationLiveClass(['id' => 3]);
    $liveClass->setRelation('course', $course);

    $notification = new LiveClassScheduledNotification($liveClass);
    $mail = $notification->toMail($instructorUser);

    expect($mail->subject)->toContain('You have a live class to handle')
        ->and($mail->introLines)->toContain('A live class has been scheduled that you are responsible for teaching.')
        ->and($notification->via($instructorUser))->toBe(['mail', 'database']);
});

it('sends a generic email to enrolled students and admins', function () {
    ['course' => $course] = notificationTestData();

    $liveClass = notificationLiveClass(['id' => 4, 'class_topic' => 'Algebra Basics']);
    $liveClass->setRelation('course', $course);

    $student = new User(['name' => 'Learner', 'email' => 'student@example.com']);
    $student->id = 33;

    $notification = new LiveClassScheduledNotification($liveClass);
    $mail = $notification->toMail($student);

    expect($mail->subject)->toContain('Live class scheduled')
        ->and($mail->subject)->not->toContain('You have a live class')
        ->and($mail->introLines)->toContain('A live class has been scheduled for your course.');
});

it('builds a database notification payload with a start route url', function () {
    $course = new Course(['instructor_id' => null]);
    $course->setRelation('instructor', null);

    $liveClass = notificationLiveClass(['id' => 5, 'class_topic' => 'Webinar: Laravel']);
    $liveClass->setRelation('course', $course);

    $recipient = new User(['name' => 'Listener', 'email' => 'listener@example.com']);
    $recipient->id = 44;

    $notification = new LiveClassScheduledNotification($liveClass);
    $payload = $notification->toArray($recipient);

    expect($payload['title'])->toBe('Live class scheduled')
        ->and($payload['body'])->toContain('Webinar: Laravel is scheduled for')
        ->and($payload['url'])->toContain('/live-classes/start/5');
});