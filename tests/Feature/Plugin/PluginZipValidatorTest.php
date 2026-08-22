<?php

use App\Exceptions\PluginInstallException;
use App\Services\Plugins\PluginZipValidator;
use Illuminate\Support\Facades\File;
use Tests\Support\PluginZipFactory;

beforeEach(function () {
    $this->validator = app(PluginZipValidator::class);
    $this->tempDir = storage_path('framework/testing/plugin-zips-'.uniqid());
    File::ensureDirectoryExists($this->tempDir);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

// ── Existing build-asset checks ────────────────────────────────────────────

it('rejects zips without public build and ssr output', function () {
    $zipPath = $this->tempDir.'/invalid.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant', includeBuild: false);

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class);
});

it('rejects unexpected paths in the zip', function () {
    $zipPath = $this->tempDir.'/unexpected.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant');

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $zip->addFromString('unexpected/file.txt', 'nope');
    $zip->close();

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class);
});

it('rejects core module names', function () {
    $zipPath = $this->tempDir.'/core.zip';
    PluginZipFactory::create($zipPath, 'Blog');

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class);
});

// ── Official registry checks ───────────────────────────────────────────────

it('rejects a plugin not in the official registry', function () {
    $zipPath = $this->tempDir.'/unofficial.zip';
    PluginZipFactory::create($zipPath, 'PiratedPlugin');

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class, 'not our official plugin');
});

// ── Module structure checks ────────────────────────────────────────────────

it('rejects a zip missing the setup marker file', function () {
    $zipPath = $this->tempDir.'/no-marker.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant', includeMarker: false);

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class, 'invalid structure');
});

it('rejects a zip missing the app/Providers directory', function () {
    $zipPath = $this->tempDir.'/no-providers.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant', includeProviders: false);

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class, 'invalid structure');
});

it('rejects a zip missing the routes directory', function () {
    $zipPath = $this->tempDir.'/no-routes.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant', includeRoutes: false);

    expect(fn () => $this->validator->validate($zipPath))
        ->toThrow(PluginInstallException::class, 'invalid structure');
});

// ── Full validation pass ───────────────────────────────────────────────────

it('validates a fully structured official plugin zip', function () {
    $zipPath = $this->tempDir.'/valid.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant');

    $result = $this->validator->validate($zipPath);

    expect($result['module_name'])->toBe('AIAssistant')
        ->and($result['module_root_in_zip'])->toBe('Modules/AIAssistant/');
});

it('cleans up the temp directory after a structure failure', function () {
    $zipPath = $this->tempDir.'/no-marker.zip';
    PluginZipFactory::create($zipPath, 'AIAssistant', includeMarker: false);

    try {
        $this->validator->validate($zipPath);
    } catch (PluginInstallException) {
        // expected
    }

    $validationDir = storage_path('app/plugins/validation');
    $leftover = File::isDirectory($validationDir)
        ? collect(File::directories($validationDir))->isNotEmpty()
        : false;

    expect($leftover)->toBeFalse();
});
