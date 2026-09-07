<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Image;
use Modules\AIAssistant\AI\Agents\InlineContentEditorAgent;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseFaq;
use Modules\Course\Models\CourseOutcome;
use Modules\Course\Models\CourseRequirement;
use Modules\Course\Models\CourseSection;

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
        'title' => 'Original Title',
        'slug' => 'original-title',
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

// ---------------------------------------------------------------------------
// Section inline edit
// ---------------------------------------------------------------------------

it('updates a section title via inline AI edit', function () {
    InlineContentEditorAgent::fake([json_encode(['title' => 'Revised Section Title'])]);

    $section = CourseSection::create([
        'title' => 'Old Section',
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [
            'prompt' => 'Make the title more professional',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('course_sections', [
        'id' => $section->id,
        'title' => 'Revised Section Title',
    ]);
});

it('rejects inline section edit without a prompt', function () {
    $section = CourseSection::create([
        'title' => 'Old Section',
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->postJson("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [])
        ->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// FAQ inline edit
// ---------------------------------------------------------------------------

it('updates a FAQ via inline AI edit', function () {
    InlineContentEditorAgent::fake([json_encode([
        'question' => 'Updated question?',
        'answer' => 'Updated answer.',
    ])]);

    $faq = CourseFaq::create([
        'course_id' => $this->course->id,
        'question' => 'Old question?',
        'answer' => 'Old answer.',
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/faq/{$faq->id}/edit", [
            'prompt' => 'Make this more concise',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('course_faqs', [
        'id' => $faq->id,
        'question' => 'Updated question?',
        'answer' => 'Updated answer.',
    ]);
});

// ---------------------------------------------------------------------------
// Outcome inline edit
// ---------------------------------------------------------------------------

it('updates an outcome via inline AI edit', function () {
    InlineContentEditorAgent::fake([json_encode(['outcome' => 'Improved outcome text'])]);

    $outcome = CourseOutcome::create([
        'course_id' => $this->course->id,
        'outcome' => 'Old outcome',
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/outcome/{$outcome->id}/edit", [
            'prompt' => 'Rewrite this as an action-oriented statement',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('course_outcomes', [
        'id' => $outcome->id,
        'outcome' => 'Improved outcome text',
    ]);
});

// ---------------------------------------------------------------------------
// Requirement inline edit
// ---------------------------------------------------------------------------

it('updates a requirement via inline AI edit', function () {
    InlineContentEditorAgent::fake([json_encode(['requirement' => 'Clearer prerequisite'])]);

    $requirement = CourseRequirement::create([
        'course_id' => $this->course->id,
        'requirement' => 'Old requirement',
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/requirement/{$requirement->id}/edit", [
            'prompt' => 'Make this clearer',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('course_requirements', [
        'id' => $requirement->id,
        'requirement' => 'Clearer prerequisite',
    ]);
});

// ---------------------------------------------------------------------------
// Language enforcement
// ---------------------------------------------------------------------------

it('passes the course language to the inline agent', function () {
    $this->course->update(['language' => 'hi']);

    InlineContentEditorAgent::fake([json_encode(['title' => 'हिंदी शीर्षक'])]);

    $section = CourseSection::create([
        'title' => 'Hindi Section',
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [
            'prompt' => 'Translate this to Hindi',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('course_sections', [
        'id' => $section->id,
        'title' => 'हिंदी शीर्षक',
    ]);
});

// ---------------------------------------------------------------------------
// Thumbnail AI generation (Media tab)
// ---------------------------------------------------------------------------

it('generates a thumbnail image via AI and updates the course', function () {
    Image::fake([base64_encode('fake-thumbnail-bytes')]);

    $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/thumbnail", [
            'prompt' => 'A modern PHP programming thumbnail with abstract code elements',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $updatedCourse = $this->course->fresh();
    expect($updatedCourse->thumbnail)->not->toBeNull();
});

it('rejects thumbnail generation without a prompt', function () {
    $this->actingAs($this->user)
        ->postJson("/dashboard/courses/ai/{$this->course->id}/thumbnail", [])
        ->assertUnprocessable();
});

// ---------------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------------

it('rejects inline edit requests from guests', function () {
    $section = CourseSection::create([
        'title' => 'Test',
        'course_id' => $this->course->id,
        'user_id' => $this->user->id,
    ]);

    $this->postJson("/dashboard/courses/ai/{$this->course->id}/section/{$section->id}/edit", [
        'prompt' => 'Improve this',
    ])->assertUnauthorized();
});
