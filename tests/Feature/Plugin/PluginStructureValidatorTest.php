<?php

use App\Exceptions\PluginInstallException;
use App\Services\Plugins\PluginStructureValidator;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->validator = app(PluginStructureValidator::class);
    $this->tempDir = storage_path('framework/testing/structure-validator-'.uniqid());
    $this->moduleName = 'TestPlugin';
    $this->modulePath = $this->tempDir.'/Modules/'.$this->moduleName;
    File::ensureDirectoryExists($this->modulePath);
});

afterEach(function () {
    File::deleteDirectory($this->tempDir);
});

/**
 * Build a fully-valid module directory at $this->modulePath.
 */
function buildValidModule(string $modulePath, string $moduleName): void
{
    File::ensureDirectoryExists($modulePath.'/app/Providers');
    File::put($modulePath.'/app/Providers/'.$moduleName.'ServiceProvider.php', '<?php');
    File::ensureDirectoryExists($modulePath.'/routes');
    File::put($modulePath.'/routes/web.php', '<?php');
    File::ensureDirectoryExists($modulePath.'/resources/js');
    File::put($modulePath.'/resources/js/index.tsx', 'export default {}');
    File::ensureDirectoryExists($modulePath.'/database/migrations');
    File::put($modulePath.'/module.json', json_encode(['name' => $moduleName]));
    File::put($modulePath.'/composer.json', json_encode(['name' => 'modules/'.strtolower($moduleName)]));
    File::put($modulePath.'/'.strtolower($moduleName), '');
}

it('passes a valid module structure', function () {
    buildValidModule($this->modulePath, $this->moduleName);

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->not->toThrow(PluginInstallException::class);
});

it('fails when the module directory does not exist', function () {
    expect(fn () => $this->validator->validate($this->modulePath.'/missing', $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when app/Providers/ is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::deleteDirectory($this->modulePath.'/app/Providers');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when routes/ is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::deleteDirectory($this->modulePath.'/routes');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when resources/ is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::deleteDirectory($this->modulePath.'/resources');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when database/ is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::deleteDirectory($this->modulePath.'/database');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when module.json is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::delete($this->modulePath.'/module.json');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when composer.json is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::delete($this->modulePath.'/composer.json');

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});

it('fails when the setup marker file is missing', function () {
    buildValidModule($this->modulePath, $this->moduleName);
    File::delete($this->modulePath.'/'.strtolower($this->moduleName));

    expect(fn () => $this->validator->validate($this->modulePath, $this->moduleName))
        ->toThrow(PluginInstallException::class);
});
