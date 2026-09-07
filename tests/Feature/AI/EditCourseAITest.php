<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AIAssistant\AI\Agents\CourseEditorAgent;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;

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

    $category = CourseCategory::create([
        'title' => 'Web Development',
        'slug' => 'web-development',
    ]);

    $this->course = Course::create([
        'title' => 'Test Course',
        'slug' => 'test-course',
        'short_description' => 'A short description.',
        'description' => '<p>Full description.</p>',
        'level' => 'beginner',
        'language' => 'English',
        'pricing_type' => 'free',
        'status' => 'draft',
        'expiry_type' => 'lifetime',
        'drip_content' => false,
        'instructor_id' => $this->instructor->id,
        'course_category_id' => $category->id,
        'course_type' => 'general',
        'user_id' => $this->user->id,
    ]);
});

it('edits a course based on a prompt', function () {
    CourseEditorAgent::fake();

    $response = $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/edit", [
            'prompt' => 'Rewrite the course description to be more engaging',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    CourseEditorAgent::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'Rewrite the course description');
    });
});

it('redirects back with a success flash after editing', function () {
    CourseEditorAgent::fake();

    $response = $this->actingAs($this->user)
        ->post("/dashboard/courses/ai/{$this->course->id}/edit", [
            'prompt' => 'Add 3 new outcomes about state management',
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
});

it('requires a prompt to edit a course', function () {
    CourseEditorAgent::fake();

    $response = $this->actingAs($this->user)
        ->postJson("/dashboard/courses/ai/{$this->course->id}/edit", []);

    $response->assertUnprocessable();

    CourseEditorAgent::assertNeverPrompted();
});

it('returns 404 for a non-existent course', function () {
    CourseEditorAgent::fake();

    $response = $this->actingAs($this->user)
        ->postJson('/dashboard/courses/ai/99999/edit', [
            'prompt' => 'Change the title',
        ]);

    $response->assertNotFound();
});

it('rejects guest requests to edit a course', function () {
    CourseEditorAgent::fake();

    $this->postJson("/dashboard/courses/ai/{$this->course->id}/edit", [
        'prompt' => 'Change the title',
    ])->assertUnauthorized();

    CourseEditorAgent::assertNeverPrompted();
});

it('rejects student requests to edit a course with a redirect', function () {
    CourseEditorAgent::fake();

    $student = User::factory()->create(['role' => 'student']);

    $this->actingAs($student)
        ->postJson("/dashboard/courses/ai/{$this->course->id}/edit", [
            'prompt' => 'Change the title',
        ])->assertRedirect();

    CourseEditorAgent::assertNeverPrompted();
});
