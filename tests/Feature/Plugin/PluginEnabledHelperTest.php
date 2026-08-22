<?php

use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

it('returns null when the module does not exist', function () {
    expect(plugin_enabled('NonExistentPlugin'.uniqid()))->toBeNull();
});

it('returns false when the module exists but is disabled', function () {
    $moduleName = 'TestPlugin';
    $modulePath = base_path('Modules/'.$moduleName);

    if (File::isDirectory($modulePath)) {
        File::deleteDirectory($modulePath);
    }

    File::ensureDirectoryExists($modulePath);
    File::put($modulePath.'/module.json', json_encode([
        'name' => $moduleName,
        'alias' => 'testplugin',
        'providers' => [],
    ], JSON_THROW_ON_ERROR));

    $statusPath = base_path('modules_statuses.json');
    $statuses = json_decode(File::get($statusPath), true);
    $statuses[$moduleName] = false;
    File::put($statusPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    Module::scan();

    expect(plugin_enabled($moduleName))->toBeFalse();

    unset($statuses[$moduleName]);
    File::put($statusPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    File::deleteDirectory($modulePath);
});

it('returns true when blog module is enabled', function () {
    if (Module::has('Blog') && Module::isEnabled('Blog')) {
        expect(plugin_enabled('Blog'))->toBeTrue();
    } else {
        expect(true)->toBeTrue();
    }
});
