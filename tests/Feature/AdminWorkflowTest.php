<?php

use App\Notifications\AdminAttentionNotification;
use Modules\Course\Models\Course;

it('builds an admin notification with a direct course review link', function () {
    $course = new Course([
        'title' => 'Algebra',
        'slug' => 'algebra',
    ]);
    $course->id = 42;

    $notification = new AdminAttentionNotification(
        'Course submitted for review',
        'Algebra is waiting for approval.',
        route('courses.edit', 42),
    );

    expect($notification->toArray((object) []))->toMatchArray([
        'title' => 'Course submitted for review',
        'body' => 'Algebra is waiting for approval.',
        'url' => route('courses.edit', 42),
    ]);
});

it('registers the admin exam governance endpoint', function () {
    expect(app('router')->getRoutes()->getByName('exam-enrollments.governance'))->not->toBeNull();
});
