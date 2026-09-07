<?php

use App\Services\Plugins\PluginManagerService;
use App\Services\Plugins\PluginModuleDiscovery;
use App\Services\Plugins\PluginOfficialRegistry;
use App\Services\Plugins\PluginRegistry;
use Illuminate\Support\Facades\File;
use Tests\Support\PluginZipFactory;

it('installs a plugin from a chunked upload file url', function () {
    $moduleName = 'ChunkedTestPlugin';
    $modulePath = base_path('Modules/'.$moduleName);
    $zipPath = storage_path('framework/testing/chunked-plugin.zip');

    if (File::isDirectory($modulePath)) {
        File::deleteDirectory($modulePath);
    }

    File::ensureDirectoryExists(dirname($zipPath));
    PluginZipFactory::create($zipPath, $moduleName);

    $publicZipPath = public_path('storage/lessons/chunked-test-plugin.zip');
    File::ensureDirectoryExists(dirname($publicZipPath));
    File::copy($zipPath, $publicZipPath);

    $fileUrl = asset('storage/lessons/chunked-test-plugin.zip');

    try {
        // Allow the test-only module name through the official registry check.
        $officialRegistry = Mockery::mock(PluginOfficialRegistry::class);
        $officialRegistry->shouldReceive('isOfficial')->andReturn(true);
        app()->instance(PluginOfficialRegistry::class, $officialRegistry);

        $installed = app(PluginManagerService::class)->installFromChunkedUrl($fileUrl);

        expect($installed)->toBe($moduleName)
            ->and(File::exists($modulePath.'/module.json'))->toBeTrue()
            ->and(File::exists($publicZipPath))->toBeFalse();
    } finally {
        if (File::isDirectory($modulePath)) {
            File::deleteDirectory($modulePath);
        }

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        if (File::exists($publicZipPath)) {
            File::delete($publicZipPath);
        }

        $statusPath = base_path('modules_statuses.json');
        $statuses = json_decode(File::get($statusPath), true);
        unset($statuses[$moduleName]);
        File::put($statusPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        app(PluginRegistry::class)->forget($moduleName);
        app(PluginModuleDiscovery::class)->refresh();
    }
});
