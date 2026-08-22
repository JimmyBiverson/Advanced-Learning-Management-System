<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Modules\AIAssistant\AI\Agents\SectionQuizGeneratorAgent;
use Modules\AIAssistant\AI\Tools\CreateQuizTool;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseSection;
use Modules\Course\Models\QuizQuestion;
use Modules\Course\Models\SectionLesson;
use Modules\Course\Models\SectionQuiz;
use Modules\Course\Services\QuizQuestionService;
use Modules\Course\Services\SectionQuizService;

uses(RefreshDatabase::class);

/** @return array{prompt: string, single_count: int, multiple_count: int, boolean_count: int} */
function quizAiPayload(string $prompt, int $single = 1, int $multiple = 0, int $boolean = 0): array
{
    return [
        'prompt' => $prompt,
        'single_count' => $single,
        'multiple_count' => $multiple,
        'boolean_count' => $boolean,
    ];
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

it('returns an error when the section has no lessons', function () {
    SectionQuizGeneratorAgent::fake(['Would have generated a quiz.']);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", quizAiPayload(
            'Create a 5-question quiz on the basics.',
            3,
            1,
            1,
        ))
        ->assertSessionHas('error');

    SectionQuizGeneratorAgent::assertNeverPrompted();
});

it('returns 404 when the section does not belong to the course', function () {
    SectionQuizGeneratorAgent::fake();

    $otherSection = CourseSection::create([
        'title' => 'Other',
        'course_id' => Course::create([
            'title' => 'Other Course',
            'slug' => 'other-course',
            'short_description' => 'x',
            'level' => 'beginner',
            'language' => 'en',
            'pricing_type' => 'free',
            'status' => 'draft',
            'expiry_type' => 'lifetime',
            'drip_content' => false,
            'user_id' => $this->user->id,
            'instructor_id' => $this->instructor->id,
            'course_category_id' => $this->course->course_category_id,
            'course_type' => 'general',
        ])->id,
    ]);

    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/{$this->course->id}/sections/{$otherSection->id}/ai/quiz", quizAiPayload(
            'Create a short quiz.',
            1,
            0,
            0,
        ))
        ->assertNotFound();

    SectionQuizGeneratorAgent::assertNeverPrompted();
});

it('runs the quiz generator agent when lessons exist', function () {
    SectionLesson::create([
        'title' => 'Variables lesson',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
        'description' => '<p>Learn about variables.</p>',
    ]);

    SectionQuizGeneratorAgent::fake(['Quiz generation complete.']);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", quizAiPayload(
            'Beginner quiz on variables.',
            2,
            1,
            0,
        ))
        ->assertRedirect()
        ->assertSessionHas('success');

    SectionQuizGeneratorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Required question counts')
            && str_contains($prompt->prompt, '2 single-choice')
            && str_contains($prompt->prompt, 'Content rule')
            && str_contains($prompt->prompt, 'Language: the course language');
    });
});

it('rejects quiz generation without a prompt', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    SectionQuizGeneratorAgent::fake();

    $this->actingAs($this->user)
        ->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", [
            'single_count' => 1,
            'multiple_count' => 0,
            'boolean_count' => 0,
        ])
        ->assertUnprocessable();

    SectionQuizGeneratorAgent::assertNeverPrompted();
});

it('rejects when all question counts are zero', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    SectionQuizGeneratorAgent::fake();

    $this->actingAs($this->user)
        ->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", quizAiPayload(
            'Some instructions here.',
            0,
            0,
            0,
        ))
        ->assertUnprocessable();

    SectionQuizGeneratorAgent::assertNeverPrompted();
});

it('rejects student access to AI quiz generation with a redirect', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    SectionQuizGeneratorAgent::fake();

    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", quizAiPayload(
            'Create a quiz.',
            1,
            0,
            0,
        ))
        ->assertRedirect();

    SectionQuizGeneratorAgent::assertNeverPrompted();
});

it('rejects guest access', function () {
    SectionLesson::create([
        'title' => 'L1',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $this->postJson("/dashboard/courses/{$this->course->id}/sections/{$this->section->id}/ai/quiz", quizAiPayload(
        'Create a quiz.',
        1,
        0,
        0,
    ))->assertUnauthorized();
});

it('creates a quiz and questions through CreateQuizTool', function () {
    SectionLesson::create([
        'title' => 'Lesson A',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $mix = ['single' => 1, 'multiple' => 1, 'boolean' => 1];

    $tool = new CreateQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $this->user->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
        $mix,
    );

    $payload = new Request([
        'title' => 'Section check quiz',
        'hours' => 0,
        'minutes' => 15,
        'seconds' => 0,
        'total_mark' => 3,
        'pass_mark' => 2,
        'retake' => 2,
        'summary' => 'Covers lesson A.',
        'questions' => [
            [
                'title' => 'Is PHP a language?',
                'type' => 'boolean',
                'options' => [],
                'answer' => ['True'],
            ],
            [
                'title' => 'Pick the smallest',
                'type' => 'single',
                'options' => ['1', '2', '3'],
                'answer' => ['1'],
            ],
            [
                'title' => 'Select evens',
                'type' => 'multiple',
                'options' => ['2', '3', '4'],
                'answer' => ['2', '4'],
            ],
        ],
    ]);

    $message = (string) $tool->handle($payload);

    expect($message)->toContain('created');

    $quiz = SectionQuiz::ofSection((int) $this->section->id)->first();
    expect($quiz)->not->toBeNull()
        ->and($quiz->title)->toBe('Section check quiz')
        ->and((int) $quiz->num_questions)->toBe(3);

    expect(QuizQuestion::ofQuiz($quiz->id)->count())->toBe(3);
});

it('rejects CreateQuizTool when question mix does not match expected counts', function () {
    SectionLesson::create([
        'title' => 'Lesson A',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $tool = new CreateQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $this->user->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
        ['single' => 1, 'multiple' => 0, 'boolean' => 0],
    );

    $payload = new Request([
        'title' => 'Bad mix quiz',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 2,
        'pass_mark' => 1,
        'retake' => 1,
        'questions' => [
            [
                'title' => 'Q1',
                'type' => 'single',
                'options' => ['A', 'B'],
                'answer' => ['A'],
            ],
            [
                'title' => 'Q2',
                'type' => 'single',
                'options' => ['C', 'D'],
                'answer' => ['C'],
            ],
        ],
    ]);

    expect((string) $tool->handle($payload))->toContain('Question mix mismatch');
});

it('blocks a second CreateQuizTool call in the same request after a successful quiz', function () {
    SectionLesson::create([
        'title' => 'Lesson A',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $mix = ['single' => 1, 'multiple' => 0, 'boolean' => 0];

    $tool = new CreateQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $this->user->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
        $mix,
    );

    $first = new Request([
        'title' => 'First quiz',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 1,
        'pass_mark' => 0,
        'retake' => 1,
        'questions' => [
            [
                'title' => 'Q1',
                'type' => 'single',
                'options' => ['A', 'B'],
                'answer' => ['A'],
            ],
        ],
    ]);

    expect((string) $tool->handle($first))->toContain('created');
    expect(SectionQuiz::ofSection((int) $this->section->id)->count())->toBe(1);

    $second = new Request([
        'title' => 'Second quiz',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 1,
        'pass_mark' => 0,
        'retake' => 1,
        'questions' => [
            [
                'title' => 'Q2',
                'type' => 'single',
                'options' => ['C', 'D'],
                'answer' => ['C'],
            ],
        ],
    ]);

    expect((string) $tool->handle($second))->toContain('already created');
    expect(SectionQuiz::ofSection((int) $this->section->id)->count())->toBe(1);
});

it('accepts questions as a JSON string and normalizes boolean answers', function () {
    SectionLesson::create([
        'title' => 'Lesson A',
        'course_id' => $this->course->id,
        'course_section_id' => $this->section->id,
        'status' => true,
        'lesson_type' => 'video_url',
        'lesson_src' => 'https://example.com',
    ]);

    $mix = ['single' => 0, 'multiple' => 0, 'boolean' => 1];

    $tool = new CreateQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $this->user->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
        $mix,
    );

    $questionsJson = json_encode([
        [
            'title' => 'Is water wet?',
            'type' => 'BOOLEAN',
            'options' => [],
            'answer' => ['true'],
        ],
    ]);

    $payload = new Request([
        'title' => 'JSON payload quiz',
        'hours' => 0,
        'minutes' => 5,
        'seconds' => 0,
        'total_mark' => 1,
        'pass_mark' => 0,
        'retake' => 1,
        'questions' => $questionsJson,
    ]);

    expect((string) $tool->handle($payload))->toContain('created');

    $quiz = SectionQuiz::ofSection((int) $this->section->id)->first();
    expect($quiz)->not->toBeNull();
    expect(QuizQuestion::ofQuiz($quiz->id)->count())->toBe(1);
    expect(QuizQuestion::ofQuiz($quiz->id)->first()->answer)->toBe(json_encode(['True']));
});

it('requires quiz output in the course display language in agent instructions', function () {
    $agent = new SectionQuizGeneratorAgent(
        '99',
        '100',
        '1',
        'Bengali',
        [
            'course_title' => 'Test',
            'section_title' => 'Sec',
            'lessons' => [['lesson_index' => 1, 'lesson_number' => 1, 'id' => 1, 'title' => 'Lesson']],
            'question_mix' => ['single' => 1, 'multiple' => 0, 'boolean' => 0],
        ],
    );

    $instructions = $agent->instructions();

    expect($instructions)->toContain('Bengali')
        ->and($instructions)->toContain('MUST write ALL output');
});

it('refuses CreateQuizTool when the section has no lessons', function () {
    $tool = new CreateQuizTool(
        (string) $this->course->id,
        (string) $this->section->id,
        (string) $this->user->id,
        app(SectionQuizService::class),
        app(QuizQuestionService::class),
    );

    $payload = new Request([
        'title' => 'Empty section quiz',
        'hours' => 0,
        'minutes' => 10,
        'seconds' => 0,
        'total_mark' => 1,
        'pass_mark' => 0,
        'retake' => 1,
        'questions' => [
            ['title' => 'Q?', 'type' => 'boolean', 'options' => [], 'answer' => ['False']],
        ],
    ]);

    expect((string) $tool->handle($payload))->toContain('no lessons');
});
