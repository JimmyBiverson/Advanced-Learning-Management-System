<?php

use App\Enums\UserType;
use App\Models\User;
use App\Services\SitemapBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

uses(RefreshDatabase::class);

it('generates the sitemap when an admin posts from maintenance', function () {
    $admin = User::factory()->create([
        'role' => UserType::ADMIN->value,
    ]);

    $path = storage_path('framework/testing-maintenance-sitemap.xml');

    config(['sitemap.path' => $path]);

    $sitemap = Sitemap::create()->add(Url::create('https://example.test/'));

    $this->mock(SitemapBuilder::class)
        ->shouldReceive('build')
        ->once()
        ->andReturn($sitemap);

    $response = $this->actingAs($admin)->post(route('system.sitemap'));

    $response->assertRedirect(route('system.maintenance.index'));
    $response->assertSessionHas('success');

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('https://example.test/');

    @unlink($path);
});

it('forbids non-admin users from generating the sitemap', function () {
    $student = User::factory()->create([
        'role' => UserType::STUDENT->value,
    ]);

    $response = $this->actingAs($student)->post(route('system.sitemap'));

    $response->assertRedirect('/');
});
