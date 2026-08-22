<?php

use App\Support\CouponDatetime;
use Tests\TestCase;

uses(TestCase::class);

it('returns null for empty coupon datetime values', function () {
    expect(CouponDatetime::normalizeForStorage(null))->toBeNull()
        ->and(CouponDatetime::normalizeForStorage(''))->toBeNull();
});

it('normalizes iso utc datetimes for storage', function () {
    expect(CouponDatetime::normalizeForStorage('2026-05-20T16:40:00.000Z'))
        ->toBe('2026-05-20 16:40:00');
});

it('normalizes datetime-local strings using the application timezone', function () {
    expect(CouponDatetime::normalizeForStorage('2026-05-20T22:40'))
        ->toBe('2026-05-20 22:40:00');
});
