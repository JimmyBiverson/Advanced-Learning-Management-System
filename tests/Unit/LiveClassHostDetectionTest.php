<?php

use App\Models\Instructor;
use App\Models\User;
use Modules\Course\Http\Controllers\LiveClassController;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseLiveClass;

it('treats an admin as host regardless of course assignment', function () {
    $liveClass = makeLiveClass(courseInstructorId: 5);
    $admin = makeUser(role: 'admin', id: 99);

    expect(isHostFor($liveClass, $admin))->toBeTrue();
});

it('treats the course instructor as host when instructor_id matches', function () {
    $liveClass = makeLiveClass(courseInstructorId: 7);
    $instructorUser = makeUser(role: 'instructor', id: 11, instructorId: 7);

    expect(isHostFor($liveClass, $instructorUser))->toBeTrue();
});

it('treats the course instructor as host when course.instructor.user_id matches', function () {
    $user = makeUser(role: 'instructor', id: 22);
    $instructor = new Instructor(['id' => 8, 'user_id' => $user->id]);
    $course = new Course(['instructor_id' => 8]);
    $course->setRelation('instructor', $instructor);

    $liveClass = new CourseLiveClass(['id' => 1, 'course_id' => 1]);
    $liveClass->setRelation('course', $course);

    expect(isHostFor($liveClass, $user))->toBeTrue();
});

it('does not treat a student as host', function () {
    $liveClass = makeLiveClass(courseInstructorId: 3);
    $student = makeUser(role: 'student', id: 50);

    expect(isHostFor($liveClass, $student))->toBeFalse();
});

it('does not treat null user as host', function () {
    $liveClass = makeLiveClass(courseInstructorId: 3);

    expect(isHostFor($liveClass, null))->toBeFalse();
});

it('does not treat an instructor for a different course as host', function () {
    $liveClass = makeLiveClass(courseInstructorId: 5);
    $otherInstructor = makeUser(role: 'instructor', id: 33, instructorId: 6);

    expect(isHostFor($liveClass, $otherInstructor))->toBeFalse();
});

it('returns false when the course has no instructor', function () {
    $course = new Course(['instructor_id' => null]);
    $course->setRelation('instructor', null);

    $liveClass = new CourseLiveClass(['id' => 1, 'course_id' => 1]);
    $liveClass->setRelation('course', $course);

    $user = makeUser(role: 'student', id: 44);

    expect(isHostFor($liveClass, $user))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function isHostFor(CourseLiveClass $liveClass, ?User $user): bool
{
    $reflection = new ReflectionClass(LiveClassController::class);
    $instance = $reflection->newInstanceWithoutConstructor();
    $method = $reflection->getMethod('isHostFor');
    $method->setAccessible(true);

    return $method->invoke($instance, $liveClass, $user);
}

function makeLiveClass(int $courseInstructorId): CourseLiveClass
{
    $instructor = new Instructor(['id' => $courseInstructorId, 'user_id' => null]);
    $course = new Course(['instructor_id' => $courseInstructorId]);
    $course->setRelation('instructor', $instructor);

    $liveClass = new CourseLiveClass(['id' => 1, 'course_id' => 1]);
    $liveClass->setRelation('course', $course);

    return $liveClass;
}

function makeUser(string $role, int $id, ?int $instructorId = null): User
{
    $user = new User(['id' => $id, 'role' => $role, 'name' => "User {$id}"]);
    $user->id = $id;

    if ($instructorId !== null) {
        $user->instructor_id = $instructorId;
    }

    return $user;
}
