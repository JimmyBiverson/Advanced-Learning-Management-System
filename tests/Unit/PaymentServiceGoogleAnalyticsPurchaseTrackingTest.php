<?php

use App\Jobs\SendGoogleAnalyticsEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Billing\Services\PaymentService;
use Tests\TestCase;

// Reflects into the private trackPurchase() rather than going through the
// full coursesBuy() write path — see PaymentServicePurchaseTrackingTest for
// why (payment_histories.course_id is a legacy NOT NULL column on SQLite).
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Setting::create(['type' => 'system', 'sub_type' => 'collaborative', 'title' => 'System', 'fields' => [
        'instructor_revenue' => 70,
        'selling_currency' => 'USD',
    ]]);

    $this->buyer = User::factory()->create(['role' => 'student', 'email' => 'buyer@example.com']);
});

it('sends purchase server-side only when the measurement protocol is enabled', function () {
    seedGoogleAnalyticsSetting([
        'analytics_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'mp_enabled' => true,
        'api_secret' => 'test-api-secret',
    ]);

    Bus::fake();

    $service = app(PaymentService::class);
    $trackPurchase = new ReflectionMethod($service, 'trackPurchase');
    $trackPurchase->setAccessible(true);

    $trackPurchase->invoke($service, (string) $this->buyer->id, 'TXN-12345', 'course', 105.0, 'Purchase Tracking Course');

    Bus::assertDispatched(SendGoogleAnalyticsEvent::class);
    expect(session('googleAnalyticsEvent'))->toBeNull();
});

it('falls back to the browser tag for purchase when the measurement protocol is disabled', function () {
    seedGoogleAnalyticsSetting([
        'analytics_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'mp_enabled' => false,
    ]);

    Bus::fake();

    $service = app(PaymentService::class);
    $trackPurchase = new ReflectionMethod($service, 'trackPurchase');
    $trackPurchase->setAccessible(true);

    $trackPurchase->invoke($service, (string) $this->buyer->id, 'TXN-54321', 'course', 105.0, 'Purchase Tracking Course');

    Bus::assertNotDispatched(SendGoogleAnalyticsEvent::class);

    expect(session('googleAnalyticsEvent'))->toBe([
        'event' => 'purchase',
        'transaction_id' => 'TXN-54321',
        'value' => 105.0,
        'currency' => 'USD',
        'content_name' => 'Purchase Tracking Course',
    ]);
});

it('does nothing when google analytics is disabled entirely', function () {
    seedGoogleAnalyticsSetting();

    Bus::fake();

    $service = app(PaymentService::class);
    $trackPurchase = new ReflectionMethod($service, 'trackPurchase');
    $trackPurchase->setAccessible(true);

    $trackPurchase->invoke($service, (string) $this->buyer->id, 'TXN-99999', 'course', 105.0, 'Purchase Tracking Course');

    Bus::assertNotDispatched(SendGoogleAnalyticsEvent::class);
    expect(session('googleAnalyticsEvent'))->toBeNull();
});
