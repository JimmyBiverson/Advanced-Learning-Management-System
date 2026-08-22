<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('redirects the home page to the installer when installed but the database is unreachable', function () {
    Storage::disk('public')->put('installed', '1');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $this->get('/')
        ->assertRedirect(route('install.index'));
});

it('redirects the home page to the installer when the application is not installed', function () {
    $this->get('/')
        ->assertRedirect(route('install.index'));
});

it('renders install step 1 when installed but the database is unreachable', function () {
    Storage::disk('public')->put('installed', '1');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => database_path('missing/database.sqlite'),
    ]);
    DB::purge('sqlite');

    $this->get('/install/step-1')
        ->assertSuccessful();
});
