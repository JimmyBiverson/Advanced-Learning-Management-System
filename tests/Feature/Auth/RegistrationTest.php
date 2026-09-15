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
    Bus::fake();
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertOk();
});

test('new users can register and are redirected to the student dashboard', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_status' => false,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.index', ['tab' => 'courses'], absolute: false));
    $response->assertSessionHas('success', 'Your account has been created successfully.');
});

test('registration completes even when the admin email notification fails', function () {
    User::factory()->create(['role' => 'admin']);

    Setting::where('type', 'smtp')->update(['fields' => [
        'mail_mailer' => 'smtp',
        'mail_host' => '127.0.0.1',
        'mail_port' => 1,
        'mail_encryption' => 'tls',
        'mail_username' => 'test@example.com',
        'mail_password' => 'secret',
        'mail_from_address' => 'noreply@example.com',
        'mail_from_name' => 'Test',
    ]]);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'recaptcha_status' => false,
    ]);

    $this->assertAuthenticated();
    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('student.index', ['tab' => 'courses'], absolute: false));
    $response->assertSessionHas('success', 'Your account has been created successfully.');
    expect(session('error'))->toBeNull();

    Bus::assertDispatched(SendMetaCapiEvent::class);
});
