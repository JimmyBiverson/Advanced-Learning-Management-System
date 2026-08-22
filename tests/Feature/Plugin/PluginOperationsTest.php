<?php

use App\Exceptions\PluginInstallException;
use App\Services\Plugins\PluginManagerService;
use Illuminate\Support\Facades\Artisan;
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
    File::put($modulePath.'/testplugin', '');

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

it('lists all non-core optional plugins', function () {
    $plugins = $this->manager->listUploadedPlugins();

    expect(collect($plugins)->pluck('name')->all())
        ->toContain('TestPlugin')
        ->not->toContain('Blog');
});

it('lists optional modules that were not uploaded through the dashboard', function () {
    File::put(storage_path('app/plugins/registry.json'), json_encode([
        'plugins' => [],
    ], JSON_THROW_ON_ERROR));

    Module::scan();

    $plugins = $this->manager->listUploadedPlugins();

    expect(collect($plugins)->pluck('name')->all())->toContain('TestPlugin');
});

it('reports needs_setup when the setup flag file exists', function () {
    $plugin = collect($this->manager->listUploadedPlugins())
        ->firstWhere('name', 'TestPlugin');

    expect($plugin)->not->toBeNull()
        ->and($plugin['needs_setup'])->toBeTrue();
});

it('reports needs_setup as false when the setup flag file is missing', function () {
    File::delete(base_path('Modules/TestPlugin/testplugin'));

    $plugin = collect($this->manager->listUploadedPlugins())
        ->firstWhere('name', 'TestPlugin');

    expect($plugin['needs_setup'])->toBeFalse();
});

it('forbids seeding on core modules', function () {
    expect(fn () => $this->manager->runUploadedPluginSeeder('Blog'))
        ->toThrow(PluginInstallException::class);
});

it('forbids seeding when setup has already been completed', function () {
    File::delete(base_path('Modules/TestPlugin/testplugin'));

    expect(fn () => $this->manager->runUploadedPluginSeeder('TestPlugin'))
        ->toThrow(PluginInstallException::class, 'Database setup has already been completed');
});

it('forbids seeding when no database seeder exists', function () {
    expect(fn () => $this->manager->runUploadedPluginSeeder('TestPlugin'))
        ->toThrow(PluginInstallException::class, 'does not have a database seeder');
});

it('runs module migration and seeder then removes the setup flag', function () {
    $moduleName = 'TestPlugin';
    $seederPath = base_path("Modules/{$moduleName}/database/seeders/{$moduleName}DatabaseSeeder.php");
    $flagPath = base_path('Modules/TestPlugin/testplugin');

    File::ensureDirectoryExists(dirname($seederPath));
    File::put($seederPath, <<<'PHP'
<?php

namespace Modules\TestPlugin\Database\Seeders;

use Illuminate\Database\Seeder;

class TestPluginDatabaseSeeder extends Seeder
{
    public function run(): void
    {
    }
}
PHP);

    require_once $seederPath;

    Artisan::shouldReceive('call')
        ->once()
        ->with('module:migrate', [
            'module' => $moduleName,
            '--force' => true,
        ]);

    Artisan::shouldReceive('call')
        ->once()
        ->with('module:seed', [
            'module' => $moduleName,
            '--class' => $moduleName.'DatabaseSeeder',
            '--force' => true,
        ]);

    $this->manager->runUploadedPluginSeeder($moduleName);

    expect(File::exists($flagPath))->toBeFalse();

    $plugin = collect($this->manager->listUploadedPlugins())
        ->firstWhere('name', 'TestPlugin');

    expect($plugin['needs_setup'])->toBeFalse();

    File::delete($seederPath);
});
