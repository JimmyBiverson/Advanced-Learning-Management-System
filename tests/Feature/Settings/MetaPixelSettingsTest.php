<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public')->put('installed', '1');
});

it('rejects meta pixel updates from non-admins', function () {
    $instructor = User::factory()->create(['role' => 'instructor']);
    $setting = seedMetaPixelSetting();

    $this->actingAs($instructor)
        ->post(route('meta-pixel.update', $setting->id), [
            'pixel_enabled' => true,
            'pixel_id' => '123456',
            'capi_enabled' => false,
        ])
        ->assertRedirect();

    expect($setting->fresh()->fields['pixel_enabled'])->toBeFalse();
});

it('requires a pixel id when the browser pixel is enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedMetaPixelSetting();

    $this->actingAs($admin)
        ->from(route('meta-pixel.index'))
        ->post(route('meta-pixel.update', $setting->id), [
            'pixel_enabled' => true,
            'pixel_id' => '',
            'capi_enabled' => false,
        ])
        ->assertSessionHasErrors('pixel_id');
});

it('requires an access token when the conversions api is enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedMetaPixelSetting();

    $this->actingAs($admin)
        ->from(route('meta-pixel.index'))
        ->post(route('meta-pixel.update', $setting->id), [
            'pixel_enabled' => false,
            'pixel_id' => '123456',
            'capi_enabled' => true,
            'access_token' => '',
        ])
        ->assertSessionHasErrors('access_token');
});

it('persists meta pixel settings for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $setting = seedMetaPixelSetting();

    $this->actingAs($admin)
        ->from(route('meta-pixel.index'))
        ->post(route('meta-pixel.update', $setting->id), [
            'pixel_enabled' => true,
            'pixel_id' => '123456789012345',
            'capi_enabled' => true,
            'access_token' => 'test-access-token',
            'test_event_code' => 'TEST12345',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $fields = $setting->fresh()->fields;

    expect($fields['pixel_enabled'])->toBeTrue();
    expect($fields['pixel_id'])->toBe('123456789012345');
    expect($fields['capi_enabled'])->toBeTrue();
    expect($fields['access_token'])->toBe('test-access-token');
    expect($fields['test_event_code'])->toBe('TEST12345');
});
