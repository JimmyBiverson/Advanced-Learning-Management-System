<?php

namespace Tests\Support;

use ZipArchive;

class PluginZipFactory
{
    /**
     * Create a ZIP that mirrors the output of `php artisan plugin:package`.
     *
     * Optional flags let individual tests omit specific items to trigger
     * validation errors without building multiple factory methods.
     *
     * @param  bool  $includeBuild  Include public/build/ and bootstrap/ssr/ assets.
     * @param  bool  $includeMarker  Include the {lowercase-name} setup marker file.
     * @param  bool  $includeProviders  Include the app/Providers/ directory.
     * @param  bool  $includeRoutes  Include the routes/ directory.
     * @param  bool  $includeResources  Include the resources/ directory.
     * @param  bool  $includeDatabase  Include the database/ directory.
     * @param  bool  $includeComposer  Include composer.json.
     */
    public static function create(
        string $path,
        string $moduleName,
        bool $includeBuild = true,
        bool $includeMarker = true,
        bool $includeProviders = true,
        bool $includeRoutes = true,
        bool $includeResources = true,
        bool $includeDatabase = true,
        bool $includeComposer = true,
    ): void {
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $moduleJson = json_encode([
            'name' => $moduleName,
            'alias' => strtolower($moduleName),
            'providers' => [
                "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider",
            ],
        ], JSON_THROW_ON_ERROR);

        $zip->addFromString("Modules/{$moduleName}/module.json", $moduleJson);

        if ($includeComposer) {
            $zip->addFromString("Modules/{$moduleName}/composer.json", json_encode([
                'name' => 'modules/'.strtolower($moduleName),
                'autoload' => [
                    'psr-4' => [
                        "Modules\\{$moduleName}\\" => 'app/',
                    ],
                ],
            ], JSON_THROW_ON_ERROR));
        }

        if ($includeProviders) {
            $zip->addFromString(
                "Modules/{$moduleName}/app/Providers/{$moduleName}ServiceProvider.php",
                "<?php\nnamespace Modules\\{$moduleName}\\Providers;\nuse Illuminate\\Support\\ServiceProvider;\nclass {$moduleName}ServiceProvider extends ServiceProvider {}\n",
            );
        }

        if ($includeRoutes) {
            $zip->addFromString("Modules/{$moduleName}/routes/web.php", "<?php\n");
        }

        if ($includeResources) {
            $zip->addFromString("Modules/{$moduleName}/resources/js/index.tsx", "export default {};\n");
        }

        if ($includeDatabase) {
            $zip->addEmptyDir("Modules/{$moduleName}/database/migrations");
        }

        if ($includeMarker) {
            $zip->addFromString("Modules/{$moduleName}/".strtolower($moduleName), '');
        }

        if ($includeBuild) {
            $zip->addFromString('public/build/manifest.json', json_encode([
                "Modules/{$moduleName}/resources/js/pages/dashboard/index.tsx" => [
                    'file' => 'assets/test-plugin.js',
                ],
            ], JSON_THROW_ON_ERROR));
            $zip->addFromString('public/build/assets/test-plugin.js', 'export default {}');
            $zip->addFromString('bootstrap/ssr/ssr-manifest.json', json_encode([], JSON_THROW_ON_ERROR));
            $zip->addFromString('bootstrap/ssr/ssr.js', 'export default {}');
        }

        $zip->close();
    }
}
