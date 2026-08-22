<?php

use App\Services\SitemapBuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

beforeEach(function () {
    Cache::flush();
    Storage::fake('public');
    Storage::disk('public')->put('installed', '1');
});

it('returns a valid xml sitemap from the route', function () {
    $sitemap = Sitemap::create()->add(Url::create('https://example.test/'));

    $this->mock(SitemapBuilder::class)
        ->shouldReceive('build')
        ->once()
        ->andReturn($sitemap);

    $response = $this->withoutMiddleware()->get(route('sitemap'));

    $response->assertSuccessful();
    expect($response->headers->get('Content-Type'))->toContain('text/xml');
    expect($response->getContent())->toContain('https://example.test/');
    expect($response->getContent())->toContain('<urlset');
});

it('generates a sitemap file via the artisan command', function () {
    $path = storage_path('framework/testing-sitemap.xml');

    config(['sitemap.path' => $path]);

    $sitemap = Sitemap::create()->add(Url::create('https://example.test/courses/all'));

    $this->mock(SitemapBuilder::class)
        ->shouldReceive('writeToFile')
        ->once()
        ->with($path)
        ->andReturnUsing(function () use ($sitemap, $path): void {
            $sitemap->writeToFile($path);
        });

    Artisan::call('sitemap:generate');

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('https://example.test/courses/all');

    @unlink($path);
});
