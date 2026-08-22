<?php

namespace Modules\Maintenance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Maintenance\Models\Backup;
use Modules\Maintenance\Services\UpdateService;
use ZipArchive;

class UpdaterController extends Controller
{
    public function __construct(
        private UpdateService $updateService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $version = file_get_contents(base_path('version.txt'));
        $isMaintenance = file_exists(storage_path('framework/maintenance.php'));

        $recentBackups = Backup::orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Maintenance/index', [
            'version' => $version,
            'isMaintenance' => $isMaintenance,
            'recentBackups' => $recentBackups,
            'flash' => [
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'success' => fn () => $request->session()->get('success'),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        if (file_exists(storage_path('framework/maintenance.php'))) {
            Artisan::call('up');

            return redirect()->back()->with('success', 'Application is now live.');
        }

        Artisan::call('down');

        return redirect()->back()->with('success', 'Application is now in maintenance mode.');
    }

    /**
     * Update application from uploaded ZIP (using chunked upload)
     */
    public function updateApp(Request $request)
    {
        $request->validate(['update_file_url' => 'required|string']);

        $secret = null;
        $tempFilePath = null;
        ini_set('max_execution_time', 600); // Set max execution time to 600 seconds (10 minutes)

        try {
            // Pre-update refresh
            Artisan::call('optimize:clear');

            // Get uploaded file path
            $updateFileUrl = $request->input('update_file_url');
            $relativePath = parse_url($updateFileUrl, PHP_URL_PATH);
            $tempFilePath = public_path($relativePath);

            $sanctumConfigPath = config_path('sanctum.php');
            if (file_exists($sanctumConfigPath)) {
                unlink($sanctumConfigPath);
                Log::info('Removed sanctum config file during update process.');
            }

            // Validate file existence
            if (! file_exists($tempFilePath)) {
                throw new \Exception('Update file not found: '.$updateFileUrl);
            }

            // Validate ZIP format by trying to open
            $zip = new ZipArchive;
            if ($zip->open($tempFilePath) !== true) {
                throw new \Exception('Invalid ZIP file');
            }
            $zip->close();

            // Put app into maintenance mode with secret
            $secret = 'update-'.now()->timestamp;
            Artisan::call('down', [
                '--secret' => $secret,
                '--render' => 'errors::503',
                '--retry' => 60, // clients told to retry after 60s
            ]);

            // Store secret in cache (so frontend can show bypass link if needed)
            Cache::put('maintenance_secret', $secret, now()->addMinutes(30));

            // Perform the update (your service)
            $this->updateService->updateApplicationFromZip($tempFilePath);

            // Delete temporary update file
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
                Log::info('Temporary update file deleted: '.basename($tempFilePath));
            }

            Log::info('Application update completed successfully');

            return redirect(route('system.update-seeder'));
        } catch (\Exception $e) {
            // Clean up on error
            if ($tempFilePath && file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }

            Log::error('Application update failed: '.$e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()->with('error', 'Update failed: '.$e->getMessage());
        } finally {
            // Always disable maintenance mode
            Artisan::call('up');
            Cache::forget('maintenance_secret');
            Log::info('Maintenance mode disabled after update operation');
        }
    }

    public function updateAppSeeder()
    {
        ini_set('max_execution_time', 600); // Set max execution time to 600 seconds (10 minutes)

        // Pre-update refresh
        Artisan::call('optimize:clear');

        // Run migrations
        Log::info('Running database migrations after update');
        Artisan::call('migrate', ['--force' => true]);

        // Run data updates/seeders
        Artisan::call('module:seed', [
            'module' => 'Maintenance',
            '--class' => 'MaintenanceDatabaseSeeder',
            '--force' => true,
        ]);
        Artisan::call('module:seed', [
            'module' => 'Blog',
            '--class' => 'BlogDatabaseSeeder',
            '--force' => true,
        ]);
        Artisan::call('module:seed', [
            'module' => 'Language',
            '--class' => 'LanguageDatabaseSeeder',
            '--force' => true,
        ]);
        Artisan::call('module:seed', [
            'module' => 'Certification',
            '--class' => 'CertificationDatabaseSeeder',
            '--force' => true,
        ]);
        Artisan::call('module:seed', [
            'module' => 'Frontend',
            '--class' => 'FrontendDatabaseSeeder',
            '--force' => true,
        ]);

        return redirect(route('system.maintenance.index'))->with('success', 'Current version seeder run successfully');
    }
}
