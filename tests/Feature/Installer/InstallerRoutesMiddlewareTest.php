<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Installer\Http\Middleware\InstallerRoutes;

beforeEach(function () {
    Storage::fake('public');
});

it('allows install routes when installed but the database is unreachable', function () {
    Storage::disk('public')->put('installed', '1');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $middleware = new InstallerRoutes;
    $request = Request::create('/install/step-1', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('redirects install routes to home when installed and the database is connected', function () {
    Storage::disk('public')->put('installed', '1');

    $middleware = new InstallerRoutes;
    $request = Request::create('/install/step-1', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toContain('/');
});
