<?php

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Billing\Models\PaymentHistory;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseCategory;
use Modules\Course\Models\CourseEnrollment;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->student = User::factory()->create(['role' => 'student']);

    $instructorUser = User::factory()->create(['role' => 'instructor']);
    $this->instructor = Instructor::create([
        'user_id' => $instructorUser->id,
        'status' => 'approved',
        'skills' => [],
        'biography' => 'Test biography',
        'resume' => '',
        'designation' => 'Instructor',
    ]);
    $instructorUser->update(['instructor_id' => $this->instructor->id]);

    $category = CourseCategory::create(['title' => 'Development', 'slug' => 'development']);

    $this->course = Course::create([
        'title' => 'Offline Payment Course',
        'slug' => 'offline-payment-course',
        'short_description' => 'Short description.',
        'level' => 'beginner',
        'language' => 'en',
        'pricing_type' => 'paid',
        'price' => 100,
        'status' => 'published',
        'expiry_type' => 'lifetime',
        'drip_content' => false,
        'user_id' => $instructorUser->id,
        'instructor_id' => $this->instructor->id,
        'course_category_id' => $category->id,
        'course_type' => 'general',
    ]);
});

it('enrolls the student when an offline course payment is verified', function () {
    $payment = PaymentHistory::forceCreate([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'amount' => 117.60,
        'tax' => 17.60,
        'payment_type' => 'offline',
        'transaction_id' => 'OFFLINE-TEST123456',
        'invoice' => 12345678,
        'admin_revenue' => 117.60,
        'purchase_type' => Course::class,
        'purchase_id' => $this->course->id,
        'meta' => [
            'status' => 'pending',
            'payment_info' => 'Bank transfer',
            'payment_date' => now()->toDateString(),
            'submitted_at' => now()->toDateTimeString(),
        ],
    ]);

    $this->actingAs($this->admin)
        ->post(route('payment-reports.offline.verify', $payment->id))
        ->assertRedirect();

    expect(CourseEnrollment::query()
        ->where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->exists())->toBeTrue();

    expect($payment->fresh()->meta['status'])->toBe('verified');
});

it('creates enrollment when re-verifying a payment that was verified without enrollment', function () {
    $payment = PaymentHistory::forceCreate([
        'user_id' => $this->student->id,
        'course_id' => $this->course->id,
        'amount' => 117.60,
        'tax' => 17.60,
        'payment_type' => 'offline',
        'transaction_id' => 'OFFLINE-ALREADYVERIFIED',
        'invoice' => 87654321,
        'admin_revenue' => 117.60,
        'purchase_type' => Course::class,
        'purchase_id' => $this->course->id,
        'meta' => [
            'status' => 'verified',
            'verified_at' => now()->subDay()->toDateTimeString(),
        ],
    ]);

    expect(CourseEnrollment::query()
        ->where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->exists())->toBeFalse();

    $this->actingAs($this->admin)
        ->post(route('payment-reports.offline.verify', $payment->id))
        ->assertRedirect();

    expect(CourseEnrollment::query()
        ->where('user_id', $this->student->id)
        ->where('course_id', $this->course->id)
        ->exists())->toBeTrue();
});
