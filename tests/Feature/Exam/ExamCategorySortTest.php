<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Exam\Models\ExamCategory;

uses(RefreshDatabase::class);

it('updates exam category sort order from sorted data', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $first = ExamCategory::create([
        'title' => 'First',
        'slug' => 'first',
        'icon' => 'folder',
        'sort' => 1,
        'status' => true,
    ]);

    $second = ExamCategory::create([
        'title' => 'Second',
        'slug' => 'second',
        'icon' => 'folder',
        'sort' => 2,
        'status' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('exam-categories.sort'), [
            'sortedData' => [
                ['id' => $second->id, 'sort' => 1],
                ['id' => $first->id, 'sort' => 2],
            ],
        ])
        ->assertRedirect();

    expect($first->fresh()->sort)->toBe(2)
        ->and($second->fresh()->sort)->toBe(1);
});

it('includes sort when loading exam categories for the index page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $category = ExamCategory::create([
        'title' => 'Alpha',
        'slug' => 'alpha',
        'icon' => 'folder',
        'status' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('exam-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $category->id)
            ->where('categories.0.sort', $category->sort));
});
