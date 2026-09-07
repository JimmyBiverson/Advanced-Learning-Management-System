<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Modules\AIAssistant\AI\Agents\SectionQuizUpdateAgent;
use Modules\AIAssistant\AI\Tools\ModifySectionQuizTool;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseSection;
use Modules\Course\Models\QuizQuestion;
use Modules\Course\Models\SectionLesson;
use Modules\Course\Models\SectionQuiz;
use Modules\Course\Services\QuizQuestionService;
use Modules\Course\Services\SectionQuizService;

uses(RefreshDatabase::class);

/** @return array{prompt: string} */
function refineQuizPayload(string $prompt): array
{
    return ['prompt' => $prompt];
}

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
});

it('refines quiz with fake agent when lessons and quiz exist', function () {
    SectionLesson::create([
        'title' => 'Variables lesson',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $quiz = SectionQuiz::create([
        'title' => 'Intro quiz',
        'duration' => '0:15:00',
        'hours' => 0,
        'minutes' => 15,
        'seconds' => 0,
        'total_mark' => 5,
        'pass_mark' => 3,
        'retake' => 2,
        'summary' => null,
        'num_questions' => 0,
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
    ]);

    SectionQuizUpdateAgent::fake(['Quiz updated by AI.']);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/quizzes/{$quiz->id}/ai", refineQuizPayload(
            'Add two beginner questions about variables.'
        ))
        ->assertRedirect()
        ->assertSessionHas('success');

    SectionQuizUpdateAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Add two beginner questions')
            && str_contains($prompt->prompt, 'Language: the course language');
    });
});

it('returns 404 when the quiz belongs to another section', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $otherSection = CourseSection::create([
        'title' => 'Other',
        'course_id' => $this->course->id,
    ]);

    $otherQuiz = SectionQuiz::create([
        'title' => 'Other quiz',
        'duration' => '0:10:00',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 3,
        'pass_mark' => 2,
        'retake' => 1,
        'summary' => null,
        'num_questions' => 0,
        'course_id' => $this->course->id,
        'course_section_id' => $otherSection->id,
    ]);

    SectionQuizUpdateAgent::fake();

    $this->actingAs($this->user)
        ->post("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/quizzes/{$otherQuiz->id}/ai", refineQuizPayload(
            'Update quiz title.'
        ))
        ->assertNotFound();

    SectionQuizUpdateAgent::assertNeverPrompted();
});

it('rejects refine without a prompt', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $quiz = SectionQuiz::create([
        'title' => 'Quiz',
        'duration' => '0:10:00',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 2,
        'pass_mark' => 1,
        'retake' => 1,
        'summary' => null,
        'num_questions' => 0,
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
    ]);

    SectionQuizUpdateAgent::fake();

    $this->actingAs($this->user)
        ->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/quizzes/{$quiz->id}/ai", [])
        ->assertUnprocessable();

    SectionQuizUpdateAgent::assertNeverPrompted();
});

it('rejects student access to refine with a redirect', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $quiz = SectionQuiz::create([
        'title' => 'Quiz',
        'duration' => '0:10:00',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 2,
        'pass_mark' => 1,
        'retake' => 1,
        'summary' => null,
        'num_questions' => 0,
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
    ]);

    SectionQuizUpdateAgent::fake();

    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/quizzes/{$quiz->id}/ai", refineQuizPayload(
            'Update the quiz.'
        ))
        ->assertRedirect();

    SectionQuizUpdateAgent::assertNeverPrompted();
});

it('rejects guest refine access', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $quiz = SectionQuiz::create([
        'title' => 'Quiz',
        'duration' => '0:10:00',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 2,
        'pass_mark' => 1,
        'retake' => 1,
        'summary' => null,
        'num_questions' => 0,
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
    ]);

    $this->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/quizzes/{$quiz->id}/ai", refineQuizPayload(
        'Please update the quiz.'
    ))->assertUnauthorized();
});

it('applies add update and delete through ModifySectionQuizTool', function () {
    SectionLesson::create([
        'title' => 'Lesson A',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $quiz = SectionQuiz::create([
        'title' => 'Section quiz',
        'duration' => '0:20:00',
        'hours' => 0,
        'minutes' => 20,
        'seconds' => 0,
        'total_mark' => 3,
        'pass_mark' => 2,
        'retake' => 2,
        'summary' => null,
        'num_questions' => 1,
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
    ]);

    $q1 = QuizQuestion::create([
        'title' => 'Old title',
        'type' => 'boolean',
        'options' => json_encode([]),
        'answer' => json_encode(['True']),
        'section_quiz_id' => $quiz->id,
    ]);

    $tool = new ModifySectionQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $quiz->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
    );

    $payload = new Request([
        'quiz_updates' => [
            'title' => 'Renamed quiz',
            'total_mark' => 4,
            'pass_mark' => 2,
        ],
        'question_operations' => [
            [
                'op' => 'update',
                'question_id' => $q1->id,
                'question' => [
                    'title' => 'Is PHP a language?',
                    'type' => 'boolean',
                    'options' => [],
                    'answer' => ['False'],
                ],
            ],
            [
                'op' => 'add',
                'question' => [
                    'title' => 'Pick one',
                    'type' => 'single',
                    'options' => ['A', 'B'],
                    'answer' => ['B'],
                ],
            ],
            [
                'op' => 'delete',
                'question_id' => $q1->id,
            ],
        ],
    ]);

    expect((string) $tool->handle($payload))->toContain('Saved');

    $quiz->refresh();
    expect($quiz->title)->toBe('Renamed quiz')
        ->and((int) $quiz->num_questions)->toBe(1);

    expect(QuizQuestion::ofQuiz($quiz->id)->count())->toBe(1);
    $remaining = QuizQuestion::ofQuiz($quiz->id)->first();
    expect($remaining->title)->toBe('Pick one')
        ->and($remaining->id)->not->toBe($q1->id);
});
