<?php

use Illuminate\Support\Facades\Validator;
use Modules\Course\Http\Requests\StoreLessonResourceRequest;

function storeLessonResourceRulesForTest(): array
{
    $rules = (new StoreLessonResourceRequest)->rules();
    $rules['section_lesson_id'] = 'required|integer';

    return $rules;
}

it('requires resource when type is link', function () {
    $validator = Validator::make([
        'title' => 'Title',
        'type' => 'link',
        'section_lesson_id' => 1,
    ], storeLessonResourceRulesForTest());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resource'))->toBeTrue();
});

it('passes when type is link and resource is present', function () {
    $validator = Validator::make([
        'title' => 'Title',
        'type' => 'link',
        'resource' => 'https://example.com/path',
        'section_lesson_id' => 1,
    ], storeLessonResourceRulesForTest());

    expect($validator->passes())->toBeTrue();
});

it('requires resource_url when type is not link', function () {
    $validator = Validator::make([
        'title' => 'Title',
        'type' => 'document',
        'section_lesson_id' => 1,
    ], storeLessonResourceRulesForTest());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('resource_url'))->toBeTrue();
});

it('passes when type is document and resource_url is present', function () {
    $validator = Validator::make([
        'title' => 'Title',
        'type' => 'document',
        'resource_url' => 'https://storage.test/file.pdf',
        'section_lesson_id' => 1,
    ], storeLessonResourceRulesForTest());

    expect($validator->passes())->toBeTrue();
});
