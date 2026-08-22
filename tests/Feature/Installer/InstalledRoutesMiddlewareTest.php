<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Installer\Http\Middleware\InstalledRoutes;

beforeEach(function () {
    Storage::fake('public');
});

it('allows install routes when the application is not installed', function () {
    $middleware = new InstalledRoutes;
    $request = Request::create('/install/step-1', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows install refresh route when the application is not installed', function () {
    $middleware = new InstalledRoutes;
    $request = Request::create('/install/refresh', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('redirects non-install routes to the installer when not installed', function () {
    $middleware = new InstalledRoutes;
    $request = Request::create('/dashboard', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toContain('/install/step-1');
});

it('allows all routes when the application is installed', function () {
    Storage::disk('public')->put('installed', '1');

    $middleware = new InstalledRoutes;
    $request = Request::create('/dashboard', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});
