<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Modules\Course\Models\CourseCoupon;
use Modules\Course\Services\CourseCouponService;
use Modules\Exam\Models\ExamCoupon;
use Modules\Exam\Services\ExamCouponService;

uses(RefreshDatabase::class);

it('excludes expired course coupons from checkout validation', function () {
    Carbon::setTestNow('2026-05-21 05:00:00');

    $user = User::factory()->create(['role' => 'admin']);

    CourseCoupon::query()->create([
        'user_id' => $user->id,
        'code' => 'EXPIRED',
        'discount' => 10,
        'discount_type' => 'percentage',
        'is_active' => true,
        'valid_to' => '2026-05-20 22:40:00',
    ]);

    $service = app(CourseCouponService::class);

    expect($service->getCourseValidCoupon('1', 'EXPIRED'))->toBeNull()
        ->and($service->getCourseValidCoupons('1'))->toHaveCount(0);

    Carbon::setTestNow();
});

it('includes active course coupons within the validity window', function () {
    Carbon::setTestNow('2026-05-20 22:00:00');

    $user = User::factory()->create(['role' => 'admin']);

    CourseCoupon::query()->create([
        'user_id' => $user->id,
        'code' => 'ACTIVE',
        'discount' => 10,
        'discount_type' => 'percentage',
        'is_active' => true,
        'valid_to' => '2026-05-20 22:40:00',
    ]);

    $service = app(CourseCouponService::class);

    expect($service->getCourseValidCoupon('1', 'ACTIVE'))->not->toBeNull()
        ->and($service->getCourseValidCoupons('1'))->toHaveCount(1);

    Carbon::setTestNow();
});

it('excludes expired exam coupons from checkout validation', function () {
    Carbon::setTestNow('2026-05-21 05:00:00');

    ExamCoupon::query()->create([
        'code' => 'EXPIRED',
        'discount' => 10,
        'discount_type' => 'percentage',
        'is_active' => true,
        'valid_to' => '2026-05-20 22:40:00',
    ]);

    $service = app(ExamCouponService::class);

    expect($service->getExamValidCoupon('1', 'EXPIRED'))->toBeNull()
        ->and($service->getExamValidCoupons('1'))->toHaveCount(0);

    Carbon::setTestNow();
});
