<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('student reports require an administrator', function () {
    $response = $this->get(route('student-reports.index'));

    $response->assertRedirect(route('login.index'));
});

test('students cannot access student reports', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('student-reports.index'))
        ->assertRedirect()
        ->assertSessionHas('error', 'You do not have permission to access this page.');
});

test('administrators can view and filter student reports', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.test']);
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@example.test']);

    $response = $this->actingAs($admin)->get(route('student-reports.index', [
        'students_search' => 'Ada',
    ]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard/student-reports/index')
            ->where('summary.students', 2)
            ->where('summary.activeStudents', 2)
            ->where('students.data.0.name', 'Ada Lovelace')
            ->where('students.data', fn ($students) => $students->count() === 1)
            ->where('filters.search', 'Ada'),
        );
});