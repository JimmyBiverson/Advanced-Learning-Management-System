<?php

use App\Jobs\SendGoogleAnalyticsEvent;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public')->put('installed', '1');

    Setting::create(['type' => 'auth', 'sub_type' => 'google', 'title' => 'Google Auth', 'fields' => [
        'active' => false, 'client_id' => '', 'client_secret' => '', 'redirect' => '',
    ]]);
    Setting::create(['type' => 'auth', 'sub_type' => 'recaptcha', 'title' => 'Recaptcha', 'fields' => [
        'active' => false, 'site_key' => '', 'secret_key' => '',
    ]]);
    Setting::create(['type' => 'smtp', 'sub_type' => null, 'title' => 'SMTP', 'fields' => [
        'mail_mailer' => 'log', 'mail_host' => '', 'mail_port' => '', 'mail_encryption' => '',
        'mail_username' => '', 'mail_password' => '', 'mail_from_address' => 'noreply@example.com', 'mail_from_name' => 'Test',
    ]]);
});

it('sends sign_up server-side only when the measurement protocol is enabled', function () {
    seedGoogleAnalyticsSetting([
        'analytics_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'mp_enabled' => true,
        'api_secret' => 'test-api-secret',
    ]);

    Bus::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_status' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    Bus::assertDispatched(SendGoogleAnalyticsEvent::class);

    // No browser fallback event — MP already sent it server-side.
    expect(session('googleAnalyticsEvent'))->toBeNull();
});

it('falls back to the browser tag for sign_up when the measurement protocol is disabled', function () {
    seedGoogleAnalyticsSetting([
        'analytics_enabled' => true,
        'measurement_id' => 'G-ABC1234567',
        'mp_enabled' => false,
    ]);

    Bus::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_status' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();

    Bus::assertNotDispatched(SendGoogleAnalyticsEvent::class);

    expect(session('googleAnalyticsEvent'))->toBe(['event' => 'sign_up']);
});

it('does nothing when google analytics is disabled entirely', function () {
    seedGoogleAnalyticsSetting();

    Bus::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_status' => false,
    ]);

    $response->assertSessionHasNoErrors();

    Bus::assertNotDispatched(SendGoogleAnalyticsEvent::class);
    expect(session('googleAnalyticsEvent'))->toBeNull();
});
