<?php

use Illuminate\Support\Facades\Route;

it('registers the course assignment management routes', function () {
    $indexRoute = Route::getRoutes()->getByName('course-assignments.index');
    $submissionsRoute = Route::getRoutes()->getByName('course-assignments.submissions');

    expect($indexRoute)->not->toBeNull()
        ->and($indexRoute->uri())->toContain('courses/{course_id}/assignments')
        ->and($submissionsRoute)->not->toBeNull()
        ->and($submissionsRoute->uri())->toContain('courses/{course_id}/assignments/{assignment_id}/submissions');
});
