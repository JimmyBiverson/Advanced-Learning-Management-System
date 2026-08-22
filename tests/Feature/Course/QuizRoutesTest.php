<?php

use Illuminate\Support\Facades\Route;

it('registers the AI section quiz generation route', function () {
    $route = Route::getRoutes()->getByName('courses.section.ai.quiz');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toContain('courses/{courseId}/sections/{sectionId}/ai/quiz');
});

it('registers quiz update and destroy routes with a named quiz parameter', function () {
    $update = Route::getRoutes()->getByName('quiz.update');
    $destroy = Route::getRoutes()->getByName('quiz.destroy');

    expect($update)->not->toBeNull()
        ->and($destroy)->not->toBeNull()
        ->and($update->uri())->toContain('{quiz}')
        ->and($destroy->uri())->toContain('{quiz}');
});
