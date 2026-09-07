<?php

use App\Http\Middleware\EnsureDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('allows install routes when the database is not connected', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $middleware = new EnsureDatabase;
    $request = Request::create('/install/step-1', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('allows install refresh route when the database is not connected', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $middleware = new EnsureDatabase;
    $request = Request::create('/install/refresh', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('redirects non-install routes to the installer when the database is not connected', function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $middleware = new EnsureDatabase;
    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->isRedirect())->toBeTrue()
        ->and($response->headers->get('Location'))->toContain('/install/step-1');
});

it('allows non-install routes when the database is connected', function () {
    $middleware = new EnsureDatabase;
    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, fn () => response('OK'));

    expect($response->getContent())->toBe('OK');
});
