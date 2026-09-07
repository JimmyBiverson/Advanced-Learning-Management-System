<?php

namespace App\Providers;

use App\Models\Setting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('system', function (): ?Setting {
            try {
                if (isDBConnected() && Schema::hasTable('settings')) {
                    return Setting::where('type', 'system')->first();
                }

                return null;
            } catch (\Throwable $th) {
                return null;
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // DB::prohibitDestructiveCommands(
        //     app()->isProduction(),
        // );

        // Password::defaults(
        //     fn(): ?Password => app()->isProduction()
        //         ? Password::min(12)
        //         ->mixedCase()
        //         ->letters()
        //         ->numbers()
        //         ->symbols()
        //         ->uncompromised()
        //         : null,
        // );

        Schema::defaultStringLength(191);

        // Fix for shared hosting missing CURL_SSLVERSION_TLSv1_2 constant
        if (!defined('CURL_SSLVERSION_TLSv1_2')) {
            define('CURL_SSLVERSION_TLSv1_2', 6); // 6 = TLSv1.2
        }

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            return env('FRONTEND_URL') . '/reset-password?token=' . $token . '&email=' . $user->email;
        });

        // Force HTTPS scheme for URLs when accessed via HTTPS
        // This ensures assets load with the correct protocol
        // Note: Proxy trust is now handled by App\Http\Middleware\TrustProxies
        if (request()->header('X-Forwarded-Proto') === 'https' || request()->secure()) {
            URL::forceScheme('https');
        }

        // // Trust proxies when running behind a reverse proxy (e.g., Docker, nginx)
        // // This allows Laravel to correctly detect HTTPS when behind a proxy
        // if (config('app.env') !== 'local' || request()->hasHeader('X-Forwarded-Proto')) {
        //     request()->setTrustedProxies(
        //         ['*'],
        //         \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
        //             \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
        //             \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
        //             \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO |
        //             \Illuminate\Http\Request::HEADER_X_FORWARDED_PREFIX
        //     );
        // }
    }
}
