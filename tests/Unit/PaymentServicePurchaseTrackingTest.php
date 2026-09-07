<?php

use App\Jobs\SendMetaCapiEvent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Modules\Billing\Services\PaymentService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Setting::create(['type' => 'system', 'sub_type' => 'collaborative', 'title' => 'System', 'fields' => [
        'instructor_revenue' => 70,
        'selling_currency' => 'USD',
    ]]);

    seedMetaPixelSetting([
        'capi_enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => 'test-access-token',
    ]);

    $this->buyer = User::factory()->create(['role' => 'student', 'email' => 'buyer@example.com']);
});

it('flashes a Purchase pixel event and dispatches the CAPI job when a purchase completes', function () {
    Bus::fake();

    $service = app(PaymentService::class);
    $trackPurchase = new ReflectionMethod($service, 'trackPurchase');
    $trackPurchase->setAccessible(true);

    $trackPurchase->invoke(
        $service,
        (string) $this->buyer->id,
        'TXN-12345',
        'course',
        105.0,
        'Purchase Tracking Course',
    );

    expect(session('metaPixelEvent'))->toBe([
        'event' => 'Purchase',
        'event_id' => 'purchase_TXN-12345',
        'value' => 105.0,
        'currency' => 'USD',
        'content_name' => 'Purchase Tracking Course',
    ]);

    Bus::assertDispatched(SendMetaCapiEvent::class);
});

it('does nothing when the purchasing user cannot be found', function () {
    Bus::fake();

    $service = app(PaymentService::class);
    $trackPurchase = new ReflectionMethod($service, 'trackPurchase');
    $trackPurchase->setAccessible(true);

    $trackPurchase->invoke($service, '999999', 'TXN-MISSING', 'course', 50.0, 'Ghost Course');

    expect(session('metaPixelEvent'))->toBeNull();
    Bus::assertNotDispatched(SendMetaCapiEvent::class);
});
