<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppConfig
{
    public function __construct(private SettingsService $settingsService) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (isInstallerRequest($request)) {
            return $next($request);
        }

        $systemSetting = $this->settingsService->getSetting(['type' => 'system']);
        $system = $systemSetting ? $systemSetting['fields'] : [];

        $storageSetting = $this->settingsService->getSetting(['type' => 'storage']);
        $storage = $storageSetting ? $storageSetting['fields'] : [];

        // App configuration
        config(['app.name' => $system['name'] ?? config('app.name')]);

        $storageDriver = $storage['storage_driver'] ?? 'local';

        // Storage configuration
        if ($storageDriver == 's3') {
            config(['media-library.disk_name' => 's3']);
            config([
                'filesystems.default' => 's3',
                'filesystems.disks.s3.key' => $storage['aws_access_key_id'] ?? '',
                'filesystems.disks.s3.secret' => $storage['aws_secret_access_key'] ?? '',
                'filesystems.disks.s3.region' => $storage['aws_default_region'] ?? '',
                'filesystems.disks.s3.bucket' => $storage['aws_bucket'] ?? '',
            ]);
        } elseif ($storageDriver == 'r2') {
            config(['media-library.disk_name' => 'r2']);
            config([
                'filesystems.default' => 'r2',
                'filesystems.disks.r2.key' => $storage['r2_access_key_id'] ?? '',
                'filesystems.disks.r2.secret' => $storage['r2_secret_access_key'] ?? '',
                'filesystems.disks.r2.region' => $storage['r2_region'] ?? 'auto',
                'filesystems.disks.r2.bucket' => $storage['r2_bucket'] ?? '',
                'filesystems.disks.r2.url' => $storage['r2_public_url'] ?? '',
                'filesystems.disks.r2.endpoint' => $storage['r2_endpoint'] ?? '',
            ]);
        } else {
            config(['media-library.disk_name' => 'public']);
            config(['filesystems.default' => 'local']);
        }

        // Check S3 configuration from config
        if (config('filesystems.default') === 's3') {
            // Check if required S3 credentials exist in config
            if (
                empty(config('filesystems.disks.s3.key')) ||
                empty(config('filesystems.disks.s3.secret')) ||
                empty(config('filesystems.disks.s3.region')) ||
                empty(config('filesystems.disks.s3.bucket'))
            ) {
                return back()->with('error', 'S3 storage configuration is incomplete. File will not upload to the S3 right now.');
            }
        }

        // Check R2 configuration from config
        if (config('filesystems.default') === 'r2') {
            // Check if required R2 credentials exist in config
            if (
                empty(config('filesystems.disks.r2.key')) ||
                empty(config('filesystems.disks.r2.secret')) ||
                empty(config('filesystems.disks.r2.bucket')) ||
                empty(config('filesystems.disks.r2.endpoint')) ||
                empty(config('filesystems.disks.r2.url'))
            ) {
                return back()->with('error', 'Cloudflare R2 storage configuration is incomplete. File will not upload to R2 right now.');
            }
        }

        return $next($request);
    }
}
