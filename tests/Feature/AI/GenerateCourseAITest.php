<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Image;
use Modules\AIAssistant\AI\Agents\CourseGeneratorAgent;
use Modules\AIAssistant\AI\Agents\TextLessonGeneratorAgent;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseSection;
use Modules\Course\Models\SectionLesson;

function fakeCourseData(): array
{
    return [
        'title' => 'OOP Course by PHP',
        'short_description' => 'A comprehensive PHP OOP course.',
        'description' => '<p>Learn OOP concepts in PHP.</p>',
        'level' => 'beginner',
        'language' => 'en',
        'pricing_type' => 'free',
        'price' => null,
        'discount' => false,
        'discount_price' => null,
        'expiry_type' => 'lifetime',
        'expiry_duration' => null,
        'sections' => [
            ['title' => 'Introduction to OOP'],
            ['title' => 'Classes and Objects'],
        ],
        'outcomes' => [
            ['outcome' => 'Understand OOP principles'],
        ],
        'requirements' => [
            ['requirement' => 'Basic PHP knowledge'],
        ],
        'faqs' => [
            ['question' => 'Do I need prior experience?', 'answer' => 'Basic PHP helps.'],
        ],
    ];
}

function fakeLessonData(string $title, string $content): array
{
    return [
        'title' => $title,
        'content' => $content,
    ];
}

function validCourseGeneratePayload(array $overrides = []): array
{
    return array_merge([
        'prompt' => 'Create a beginner React course with practical projects',
        'category_id' => null,
        'section_count' => 2,
        'faq_count' => 1,
        'requirement_count' => 1,
        'outcome_count' => 1,
        'generate_text_lessons' => false,
    ], $overrides);
}

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

    $this->category = CourseCategory::create([
        'title' => 'Web Development',
        'slug' => 'web-development',
    ]);
});

it('generates a full course from a prompt', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
        ]));

    $response->assertRedirect();

    CourseGeneratorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Create a beginner React course with practical projects');
    });
});

it('creates the requested number of sections', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'section_count' => 2,
        ]))
        ->assertRedirect();

    $course = Course::query()->where('instructor_id', $this->instructor->id)->latest('id')->first();

    expect($course)->not->toBeNull();
    expect(CourseSection::query()->where('course_id', $course->id)->count())->toBe(2);
});

it('generates text lessons per section when enabled', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    TextLessonGeneratorAgent::fake([
        fakeLessonData('Lesson A', '<p>Content A</p>'),
        fakeLessonData('Lesson B', '<p>Content B</p>'),
        fakeLessonData('Lesson C', '<p>Content C</p>'),
        fakeLessonData('Lesson D', '<p>Content D</p>'),
    ]);

    $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'section_count' => 2,
            'generate_text_lessons' => 1,
            'text_lessons_per_section' => 2,
        ]))
        ->assertRedirect();

    $course = Course::query()->where('instructor_id', $this->instructor->id)->latest('id')->first();

    expect(SectionLesson::query()->where('course_id', $course->id)->where('lesson_type', 'text')->count())->toBe(4);

    TextLessonGeneratorAgent::assertPrompted(fn () => true);
});

it('creates two lessons in each section when using integer flags from the UI', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    $lessonResponses = [];

    for ($i = 1; $i <= 10; $i++) {
        $lessonResponses[] = fakeLessonData("Lesson {$i}", "<p>Content {$i}</p>");
    }

    TextLessonGeneratorAgent::fake($lessonResponses);

    $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'section_count' => 5,
            'generate_text_lessons' => 1,
            'text_lessons_per_section' => 2,
        ]))
        ->assertRedirect();

    $course = Course::query()->where('instructor_id', $this->instructor->id)->latest('id')->first();

    $sections = CourseSection::query()->where('course_id', $course->id)->orderBy('sort')->get();

    expect($sections)->toHaveCount(5);

    foreach ($sections as $section) {
        expect(
            SectionLesson::query()
                ->where('course_section_id', $section->id)
                ->where('lesson_type', 'text')
                ->count()
        )->toBe(2);
    }
});

it('requires text lessons per section when text lesson generation is enabled', function () {
    $payload = validCourseGeneratePayload([
        'category_id' => $this->category->id,
        'generate_text_lessons' => 1,
    ]);
    unset($payload['text_lessons_per_section']);

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', $payload);

    $response->assertSessionHasErrors('text_lessons_per_section');
});

it('creates a course in the database after generation', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
        ]));

    $this->assertDatabaseHas('courses', [
        'instructor_id' => $this->instructor->id,
        'course_category_id' => $this->category->id,
        'status' => 'draft',
    ]);
});

it('redirects back after generation with a success flash', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'prompt' => 'Create a beginner PHP course',
        ]));

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('requires a prompt to generate a course', function () {
    CourseGeneratorAgent::fake();

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'prompt' => '',
        ]));

    $response->assertSessionHasErrors(['prompt']);
});

it('requires a category to generate a course', function () {
    CourseGeneratorAgent::fake();

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'prompt' => 'Create a Laravel course',
        ]));

    $response->assertSessionHasErrors(['category_id']);
});

it('rejects requests from guests', function () {
    CourseGeneratorAgent::fake();

    $this->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
        'category_id' => $this->category->id,
        'prompt' => 'Create a Laravel course',
    ]))->assertRedirect(route('login.index'));

    CourseGeneratorAgent::assertNeverPrompted();
});

it('rejects requests from students with a redirect', function () {
    CourseGeneratorAgent::fake();

    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'category_id' => $this->category->id,
            'prompt' => 'Create a Laravel course',
        ]))->assertRedirect();

    CourseGeneratorAgent::assertNeverPrompted();
});

it('validates prompt minimum length', function () {
    CourseGeneratorAgent::fake();

    $response = $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'prompt' => 'short',
            'category_id' => $this->category->id,
        ]));

    $response->assertSessionHasErrors(['prompt']);
});

it('generates thumbnails using the platform image provider when OpenRouter is configured', function () {
    CourseGeneratorAgent::fake([fakeCourseData()]);
    Image::fake([base64_encode('fake-thumbnail-bytes')]);

    seedAiAssistantSetting([
        'token_limit' => 100_000,
        'reset_period' => 'monthly',
        'provider' => 'openrouter',
        'model' => 'openai/gpt-4o',
        'api_key' => 'sk-or-v1-test'.str_repeat('x', 40),
        'is_active' => true,
    ]);

    $this->actingAs($this->user)
        ->post('/dashboard/courses/ai/generate', validCourseGeneratePayload([
            'prompt' => 'Create a beginner PHP course with several sections',
            'category_id' => $this->category->id,
            'generate_thumbnail' => true,
        ]))
        ->assertRedirect();

    Image::assertGenerated(fn ($prompt) => $prompt->contains('Professional educational'));

    $course = Course::query()->where('instructor_id', $this->instructor->id)->latest('id')->first();
    expect($course)->not->toBeNull();
    expect($course->thumbnail)->not->toBeNull()->not->toBe('');
});
