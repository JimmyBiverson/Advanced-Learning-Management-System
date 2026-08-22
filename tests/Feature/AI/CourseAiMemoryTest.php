<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AIAssistant\AI\Agents\InlineContentEditorAgent;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseAiInteraction;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseSection;
use Modules\Course\Services\CourseAiMemoryService;

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
        'title' => 'Memory Test Course',
        'slug' => 'memory-test-course',
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
});

it('records each inline section AI edit under a scoped context key', function () {
    InlineContentEditorAgent::fake([
        json_encode(['title' => 'First revision']),
        json_encode(['title' => 'Second revision']),
    ]);

    $section = CourseSection::create([
        'title' => 'Original',
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);

    $contextKey = CourseAiMemoryService::contextForSection((int) $section->id);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [
            'prompt' => 'First pass: make it professional',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CourseAiInteraction::query()->where('course_id', $this->course->id)->where('context_key', $contextKey)->count())->toBe(1);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [
            'prompt' => 'Second pass: shorten the title',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(CourseAiInteraction::query()->where('course_id', $this->course->id)->where('context_key', $contextKey)->count())->toBe(2);

    $latest = CourseAiInteraction::query()->where('context_key', $contextKey)->latest('id')->first();
    expect($latest)->not->toBeNull();
    expect($latest->user_prompt)->toContain('Second pass');
});

it('includes prior scoped interactions in the memory appendix', function () {
    $service = app(CourseAiMemoryService::class);

    $service->record(
        (int) $this->course->id,
        (int) $this->user->id,
        CourseAiMemoryService::contextForSection(99),
        'First prompt for this section',
        'Title set to A'
    );

    $service->record(
        (int) $this->course->id,
        (int) $this->user->id,
        CourseAiMemoryService::contextForSection(99),
        'Second prompt for this section',
        'Title set to B'
    );

    $appendix = $service->buildMemoryAppendix((int) $this->course->id, CourseAiMemoryService::contextForSection(99));

    expect($appendix)->toContain('First prompt for this section');
    expect($appendix)->toContain('Second prompt for this section');
    expect($appendix)->toContain('Title set to B');
});
