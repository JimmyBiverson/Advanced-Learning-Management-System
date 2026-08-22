<?php

use App\Jobs\SendMetaCapiEvent;
use App\Models\Setting;
use App\Models\User;
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
    seedMetaPixelSetting([
        'pixel_enabled' => true,
        'pixel_id' => '123456789012345',
        'capi_enabled' => true,
        'access_token' => 'test-access-token',
    ]);
});

it('flashes a CompleteRegistration pixel event and dispatches the CAPI job on registration', function () {
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

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect(session('metaPixelEvent'))->toBe([
        'event' => 'CompleteRegistration',
        'event_id' => 'reg_'.$user->id,
    ]);

    Bus::assertDispatched(SendMetaCapiEvent::class);
});
