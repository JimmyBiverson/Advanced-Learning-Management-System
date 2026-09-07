<?php

use App\Services\GoogleAnalyticsMeasurementProtocolService;
use App\Services\GoogleAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('does not call the measurement protocol when it is disabled', function () {
    seedGoogleAnalyticsSetting();
    Http::fake();

    app(GoogleAnalyticsMeasurementProtocolService::class)->send('sign_up', 'client.123', '7');

    Http::assertNothingSent();
});

it('sends client_id, user_id, and event params without any PII', function () {
    seedGoogleAnalyticsSetting([
        'mp_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'api_secret' => 'test-api-secret',
    ]);

    Http::fake([
        'www.google-analytics.com/mp/collect*' => Http::response('', 204),
    ]);

    app(GoogleAnalyticsMeasurementProtocolService::class)->send(
        'purchase',
        '1053244267.1642015504',
        '7',
        ['transaction_id' => 'TXN-1', 'value' => 49.99, 'currency' => 'USD'],
    );

    Http::assertSent(function ($request) {
        expect($request->url())->toContain('www.google-analytics.com/mp/collect')
            ->and($request->url())->toContain('measurement_id=G-ABC1234567')
            ->and($request->url())->toContain('api_secret=test-api-secret')
            ->and($request->url())->not->toContain('debug');

        $data = $request->data();

        expect($data['client_id'])->toBe('1053244267.1642015504')
            ->and($data['user_id'])->toBe('7')
            ->and($data)->not->toHaveKey('email')
            ->and($data['events'][0]['name'])->toBe('purchase')
            ->and($data['events'][0]['params'])->toBe([
                'transaction_id' => 'TXN-1',
                'value' => 49.99,
                'currency' => 'USD',
            ]);

        return true;
    });
});

it('synthesizes a client_id when none was provided', function () {
    seedGoogleAnalyticsSetting([
        'mp_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'api_secret' => 'test-api-secret',
    ]);

    Http::fake([
        'www.google-analytics.com/mp/collect*' => Http::response('', 204),
    ]);

    app(GoogleAnalyticsMeasurementProtocolService::class)->send('sign_up', null, '7');

    Http::assertSent(function ($request) {
        return str_starts_with($request->data()['client_id'], 'server.7');
    });
});

it('routes to the debug endpoint when debug mode is enabled', function () {
    seedGoogleAnalyticsSetting([
        'mp_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'api_secret' => 'test-api-secret',
        'debug_mode' => true,
    ]);

    Http::fake([
        'www.google-analytics.com/debug/mp/collect*' => Http::response(['validationMessages' => []], 200),
    ]);

    app(GoogleAnalyticsMeasurementProtocolService::class)->send('sign_up', 'client.123', '7');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/debug/mp/collect'));
});

it('extracts the client_id from a standard _ga cookie', function () {
    seedGoogleAnalyticsSetting();

    $clientId = app(GoogleAnalyticsService::class)
        ->extractClientIdFromCookie('GA1.1.1053244267.1642015504');

    expect($clientId)->toBe('1053244267.1642015504');
});

it('returns null when the _ga cookie is missing or malformed', function () {
    seedGoogleAnalyticsSetting();
    $service = app(GoogleAnalyticsService::class);

    expect($service->extractClientIdFromCookie(null))->toBeNull();
    expect($service->extractClientIdFromCookie('not-a-ga-cookie'))->toBeNull();
});

it('reports the browser analytics tag as disabled without a measurement id even if the toggle is on', function () {
    seedGoogleAnalyticsSetting(['analytics_enabled' => true, 'measurement_id' => '']);

    expect(app(GoogleAnalyticsService::class)->isAnalyticsEnabled())->toBeFalse();
});
