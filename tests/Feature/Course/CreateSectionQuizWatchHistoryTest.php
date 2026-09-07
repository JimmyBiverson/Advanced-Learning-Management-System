<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseSection;
use Modules\Course\Models\SectionLesson;
use Modules\Course\Models\SectionQuiz;
use Modules\Course\Models\WatchHistory;
use Modules\Course\Services\SectionQuizService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'instructor']);
    $this->instructor = Instructor::create([
        'user_id' => $this->user->id,
        'status' => 'approved',
        'skills' => [],
        'biography' => 'Test biography',
        'resume' => '',
        'designation' => 'Instructor',
    ]);
    $this->user->update(['instructor_id' => $this->instructor->id]);

    $category = CourseCategory::create(['title' => 'Dev', 'slug' => 'dev']);

    $this->course = Course::create([
        'title' => 'Test Course',
        'slug' => 'test-course',
        'short_description' => 'Short.',
        'level' => 'beginner',
        'language' => 'en',
        'pricing_type' => 'free',
        'status' => 'draft',
        'expiry_type' => 'lifetime',
        'drip_content' => false,
        'user_id' => $this->user->id,
        'instructor_id' => $this->instructor->id,
        'course_category_id' => $category->id,
        'course_type' => 'general',
    ]);

    $this->section = CourseSection::create([
        'title' => 'Introduction',
        'course_id' => $this->course->id,
    ]);

    SectionLesson::create([
        'title' => 'First lesson',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com/video',
    ]);
});

it('creates a section quiz when the instructor has no watch history yet', function () {
    expect(WatchHistory::ofCourse($this->course->id)->ofUser($this->user->id)->exists())->toBeFalse();

    $quiz = app(SectionQuizService::class)->createQuiz([
        'title' => 'Intro quiz',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'hours' => 0,
        'minutes' => 15,
        'seconds' => 0,
        'total_mark' => 10,
        'pass_mark' => 7,
        'retake' => 3,
        'summary' => null,
    ], (string) $this->user->id);

    expect($quiz)->toBeInstanceOf(SectionQuiz::class)
        ->and(SectionQuiz::ofSection((int) $this->section->id)->where('id', $quiz->id)->exists())->toBeTrue();

    $history = WatchHistory::ofCourse($this->course->id)->ofUser($this->user->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->current_section_id)->not->toBeNull()
        ->and($history->current_section_id)->toBe((string) $this->section->id)
        ->and($history->current_watching_type)->toBe('lesson');
});
