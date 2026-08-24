<?php

use App\Models\User;
use App\Models\Setting;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::create([
        'type' => 'smtp',
        'title' => 'SMTP',
        'fields' => [
            'mail_mailer' => 'array',
            'mail_host' => 'localhost',
            'mail_port' => 2525,
            'mail_encryption' => null,
            'mail_username' => null,
            'mail_password' => null,
            'mail_from_name' => 'Test',
            'mail_from_address' => 'test@example.com',
        ],
    ]);
});

test('sends verification notification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('home'));

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

test('does not send verification notification if email is verified', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect(route('dashboard', absolute: false));

    Notification::assertNothingSent();
});
