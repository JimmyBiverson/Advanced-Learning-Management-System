<?php

use App\Exceptions\PluginInstallException;
use App\Services\Plugins\PluginManagerService;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;

beforeEach(function () {
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

    File::ensureDirectoryExists(storage_path('app/plugins'));
    File::put(storage_path('app/plugins/registry.json'), json_encode([
        'plugins' => [
            $moduleName => [
                'installed_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    Module::scan();

    $this->manager = app(PluginManagerService::class);
});

afterEach(function () {
    $modulePath = base_path('Modules/TestPlugin');

    if (File::isDirectory($modulePath)) {
        File::deleteDirectory($modulePath);
    }

    $statusPath = base_path('modules_statuses.json');
    $statuses = json_decode(File::get($statusPath), true);
    unset($statuses['TestPlugin']);
    File::put($statusPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if (File::exists(storage_path('app/plugins/registry.json'))) {
        File::delete(storage_path('app/plugins/registry.json'));
    }
});

it('forbids toggling a core module', function () {
    expect(fn () => $this->manager->toggle('Blog', false))
        ->toThrow(PluginInstallException::class);
});

it('enables and disables an uploaded plugin', function () {
    $this->manager->toggle('TestPlugin', true);

    expect(Module::isEnabled('TestPlugin'))->toBeTrue();

    $this->manager->toggle('TestPlugin', false);

    expect(Module::isEnabled('TestPlugin'))->toBeFalse();
});
