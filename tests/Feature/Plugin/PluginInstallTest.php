<?php

use App\Services\Plugins\PluginManagerService;
use App\Services\Plugins\PluginModuleDiscovery;
use App\Services\Plugins\PluginOfficialRegistry;
use App\Services\Plugins\PluginRegistry;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\Facades\Module;
use Tests\Support\PluginZipFactory;

beforeEach(function () {
    $this->moduleName = 'InstallTestPlugin';
    $this->modulePath = base_path('Modules/'.$this->moduleName);
    $this->buildPath = base_path('public/build');
    $this->ssrPath = base_path('bootstrap/ssr');
    $this->zipPath = storage_path('framework/testing/install-plugin-'.uniqid().'.zip');

    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }

    File::ensureDirectoryExists(dirname($this->zipPath));
    PluginZipFactory::create($this->zipPath, $this->moduleName);

    // Allow the test-only module name through the official registry check.
    $registry = Mockery::mock(PluginOfficialRegistry::class);
    $registry->shouldReceive('isOfficial')->andReturn(true);
    app()->instance(PluginOfficialRegistry::class, $registry);

    $this->manager = app(PluginManagerService::class);
});

afterEach(function () {
    if (File::isDirectory($this->modulePath)) {
        File::deleteDirectory($this->modulePath);
    }

    if (File::exists($this->zipPath)) {
        File::delete($this->zipPath);
    }

    $statusPath = base_path('modules_statuses.json');
    $statuses = json_decode(File::get($statusPath), true);
    unset($statuses[$this->moduleName]);
    File::put($statusPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    app(PluginRegistry::class)->forget($this->moduleName);
    app(PluginModuleDiscovery::class)->refresh();
});

it('installs module source, build assets, and ssr output from the zip', function () {
    $moduleName = $this->manager->installFromZipPath($this->zipPath);

    expect($moduleName)->toBe($this->moduleName)
        ->and(File::exists($this->modulePath.'/module.json'))->toBeTrue()
        ->and(File::exists($this->buildPath.'/manifest.json'))->toBeTrue()
        ->and(File::exists($this->ssrPath.'/ssr.js'))->toBeTrue()
        ->and(Module::has($this->moduleName))->toBeTrue()
        ->and(app(PluginRegistry::class)->isUploadedPlugin($this->moduleName))->toBeTrue();
});
