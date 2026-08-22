<?php

use App\Services\MetaConversionsApiService;
use App\Services\MetaPixelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not call the graph api when the conversions api is disabled', function () {
    seedMetaPixelSetting();
    Http::fake();

    app(MetaConversionsApiService::class)->send('CompleteRegistration', 'reg_1', [
        'email' => 'buyer@example.com',
    ]);

    Http::assertNothingSent();
});

it('sends a sha256-hashed payload to the graph api when enabled', function () {
    seedMetaPixelSetting([
        'capi_enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => 'test-access-token',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
    ]);

    app(MetaConversionsApiService::class)->send(
        'Purchase',
        'purchase_txn_1',
        [
            'email' => ' Buyer@Example.com ',
            'external_id' => '42',
            'ip' => '127.0.0.1',
            'user_agent' => 'PestTestAgent/1.0',
            'fbc' => 'fb.1.111.abc',
            'fbp' => 'fb.1.222.xyz',
        ],
        ['value' => 49.99, 'currency' => 'USD'],
        'https://example.test/checkout/success',
    );

    Http::assertSent(function ($request) {
        expect($request->url())->toContain('graph.facebook.com')
            ->and($request->url())->toContain('123456789012345');

        $data = $request->data();

        expect($data['access_token'])->toBe('test-access-token');

        $event = $data['data'][0];

        expect($event['event_name'])->toBe('Purchase')
            ->and($event['event_id'])->toBe('purchase_txn_1')
            ->and($event['event_source_url'])->toBe('https://example.test/checkout/success')
            ->and($event['custom_data'])->toBe(['value' => 49.99, 'currency' => 'USD']);

        // Email must be lowercased, trimmed, and SHA-256 hashed — never sent plain.
        expect($event['user_data']['em'][0])->toBe(hash('sha256', 'buyer@example.com'))
            ->and($event['user_data']['external_id'][0])->toBe(hash('sha256', '42'))
            ->and($event['user_data']['client_ip_address'])->toBe('127.0.0.1')
            ->and($event['user_data']['client_user_agent'])->toBe('PestTestAgent/1.0')
            ->and($event['user_data']['fbc'])->toBe('fb.1.111.abc')
            ->and($event['user_data']['fbp'])->toBe('fb.1.222.xyz');

        return true;
    });
});

it('includes the test event code when configured', function () {
    seedMetaPixelSetting([
        'capi_enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => 'test-access-token',
        'test_event_code' => 'TEST12345',
    ]);

    Http::fake([
        'graph.facebook.com/*' => Http::response(['events_received' => 1], 200),
    ]);

    app(MetaConversionsApiService::class)->send('CompleteRegistration', 'reg_1', [
        'email' => 'buyer@example.com',
    ]);

    Http::assertSent(function ($request) {
        return ($request->data()['test_event_code'] ?? null) === 'TEST12345';
    });
});

it('reports the browser pixel as disabled without a pixel id even if the toggle is on', function () {
    seedMetaPixelSetting(['pixel_enabled' => true, 'pixel_id' => '']);

    expect(app(MetaPixelService::class)->isPixelEnabled())->toBeFalse();
});
