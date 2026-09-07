<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public')->put('installed', '1');
});

it('rejects google analytics updates from non-admins', function () {
    $instructor = User::factory()->create(['role' => 'instructor']);
    $setting = seedGoogleAnalyticsSetting();

    $this->actingAs($instructor)
        ->post(route('google-analytics.update', $setting->id), [
            'analytics_enabled' => true,
            'measurement_id' => 'G-ABC1234567',
            'mp_enabled' => false,
            'debug_mode' => false,
        ])
        ->assertRedirect();

    expect($setting->fresh()->fields['analytics_enabled'])->toBeFalse();
});

it('requires a measurement id when analytics is enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedGoogleAnalyticsSetting();

    $this->actingAs($admin)
        ->from(route('google-analytics.index'))
        ->post(route('google-analytics.update', $setting->id), [
            'analytics_enabled' => true,
            'measurement_id' => '',
            'mp_enabled' => false,
            'debug_mode' => false,
        ])
        ->assertSessionHasErrors('measurement_id');
});

it('rejects a malformed measurement id', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedGoogleAnalyticsSetting();

    $this->actingAs($admin)
        ->from(route('google-analytics.index'))
        ->post(route('google-analytics.update', $setting->id), [
            'analytics_enabled' => true,
            'measurement_id' => 'not-a-valid-id',
            'mp_enabled' => false,
            'debug_mode' => false,
        ])
        ->assertSessionHasErrors('measurement_id');
});

it('requires an api secret when the measurement protocol is enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedGoogleAnalyticsSetting();

    $this->actingAs($admin)
        ->from(route('google-analytics.index'))
        ->post(route('google-analytics.update', $setting->id), [
            'analytics_enabled' => false,
            'measurement_id' => 'G-ABC1234567',
            'mp_enabled' => true,
            'api_secret' => '',
            'debug_mode' => false,
        ])
        ->assertSessionHasErrors('api_secret');
});

it('persists google analytics settings for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedGoogleAnalyticsSetting();

    $this->actingAs($admin)
        ->from(route('google-analytics.index'))
        ->post(route('google-analytics.update', $setting->id), [
            'analytics_enabled' => true,
            'measurement_id' => 'G-ABC1234567',
            'mp_enabled' => true,
            'api_secret' => 'test-api-secret',
            'debug_mode' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $fields = $setting->fresh()->fields;

    expect($fields['analytics_enabled'])->toBeTrue();
    expect($fields['measurement_id'])->toBe('G-ABC1234567');
    expect($fields['mp_enabled'])->toBeTrue();
    expect($fields['api_secret'])->toBe('test-api-secret');
    expect($fields['debug_mode'])->toBeTrue();
});
