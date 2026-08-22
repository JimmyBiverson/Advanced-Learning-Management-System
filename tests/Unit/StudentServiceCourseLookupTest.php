<?php

test('getEnrolledCourse uses findOrFail on Course query', function () {
    $path = dirname(__DIR__, 2).'/app/Services/StudentService.php';
    $contents = file_get_contents($path);

    expect($contents)->not->toContain('finedOrFail')
        ->and($contents)->toContain('Course::query()')
        ->and($contents)->toContain('->findOrFail($id)');
});
