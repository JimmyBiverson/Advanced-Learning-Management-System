<?php

use Illuminate\Support\Facades\Route;

it('registers the live-class store, update and destroy routes with smtpConfig middleware', function () {
    $routes = ['live-classes.store', 'live-classes.update', 'live-classes.destroy'];

    foreach ($routes as $name) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull()
            ->and($route->getName())->toBe($name)
            ->and($route->gatherMiddleware())->toContain('smtpConfig');
    }
});

it('registers the live-class player start route', function () {
    $route = Route::getRoutes()->getByName('live-class.start');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toContain('live-classes/start/{id}')
        ->and($route->methods())->toContain('GET');
});

it('registers the live-class zoom signature route', function () {
    $route = Route::getRoutes()->getByName('live-class.signature');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toContain('live-classes/signature/{id}')
        ->and($route->methods())->toContain('GET');
});