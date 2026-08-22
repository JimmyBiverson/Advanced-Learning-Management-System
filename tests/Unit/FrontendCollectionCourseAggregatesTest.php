<?php

test('frontend collection model loads course card aggregates for api carousels', function () {
    $path = dirname(__DIR__, 2).'/Modules/Frontend/app/Models/FrontendCollection.php';
    $contents = file_get_contents($path);

    expect($contents)->toContain("'reviews'")
        ->and($contents)->toContain('applyCourseListAggregates')
        ->and($contents)->toContain("withAvg('reviews as average_rating', 'rating')")
        ->and($contents)->toContain('lessons_duration');
});
