<?php

use Modules\Exam\Http\Middleware\CheckExamEnrollMiddleware;

it('protects every student exam attempt action with enrollment governance', function () {
    foreach ([
        'exam-attempts.start',
        'exam-attempts.take',
        'exam-attempts.submit',
        'exam-attempts.abandon',
    ] as $routeName) {
        $route = app('router')->getRoutes()->getByName($routeName);

        expect($route)->not->toBeNull()
            ->and($route->gatherMiddleware())->toContain(CheckExamEnrollMiddleware::class);
    }
});

it('exposes governance fields on exam enrollments', function () {
    $enrollment = new \Modules\Exam\Models\ExamEnrollment;

    expect($enrollment->getFillable())->toContain('access_granted', 'payment_status', 'amount_paid', 'results_locked');
});
