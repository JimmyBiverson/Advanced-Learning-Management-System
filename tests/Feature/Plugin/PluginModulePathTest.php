<?php

use App\Services\Plugins\PluginPackagePaths;
use App\Services\Plugins\PluginZipValidator;
use Illuminate\Support\Facades\File;

it('resolves the canonical module directory from the module name', function () {
    $validator = app(PluginZipValidator::class);

    expect($validator->moduleDirectory('AIAssistant'))
        ->toBe(app(PluginPackagePaths::class)->moduleDirectory('AIAssistant'));
});

it('uses the module name for the directory even when module_path points elsewhere', function () {
    $misplacedPath = base_path('Modules/TEST');
    File::ensureDirectoryExists($misplacedPath);
    File::put($misplacedPath.'/module.json', json_encode([
        'name' => 'AIAssistant',
        'alias' => 'aiassistant',
        'providers' => [],
    ], JSON_THROW_ON_ERROR));

    expect(app(PluginZipValidator::class)->moduleDirectory('AIAssistant'))
        ->toBe(base_path('Modules/AIAssistant'))
        ->not->toBe(module_path('AIAssistant'));

    File::deleteDirectory($misplacedPath);
});

it('detects when the canonical module directory exists on disk', function () {
    $path = base_path('Modules/PathTestPlugin');
    File::ensureDirectoryExists($path);
    File::put($path.'/module.json', json_encode(['name' => 'PathTestPlugin'], JSON_THROW_ON_ERROR));

    expect(app(PluginZipValidator::class)->moduleExistsOnDisk('PathTestPlugin'))->toBeTrue();

    File::deleteDirectory($path);
});
