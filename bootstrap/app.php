<?php

use App\Http\Middleware\AppConfig;
use App\Http\Middleware\AuthConfig;
use App\Http\Middleware\EnsureDatabase;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IntroCustomize;
use App\Http\Middleware\IpDetectorMiddleware;
use App\Http\Middleware\SmtpConfig;
use App\Http\Middleware\SystemCollaborative;
use App\Http\Middleware\UserRole;
use App\Http\Middleware\ValidateEmailVerificationSignature;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Validation\ValidationException;
use Modules\Installer\Http\Middleware\InstalledRoutes;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn() => route('login.index'));

        // Trust proxies - must run early to detect HTTPS correctly
        $middleware->trustProxies(at: '*');

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            InstalledRoutes::class,
            EnsureDatabase::class,
            AppConfig::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->preventRequestsDuringMaintenance(except: [
            'system/*',
            'install/refresh',
        ]);

        $middleware->alias([
            'role' => UserRole::class,
            'authConfig' => AuthConfig::class,
            'smtpConfig' => SmtpConfig::class,
            'customize' => IntroCustomize::class,
            'collaborative' => SystemCollaborative::class,
            'ip.detector' => IpDetectorMiddleware::class,
            'email.signature' => ValidateEmailVerificationSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e, $request) {
            if (! isDBConnected() && ! isInstallerRequest($request)) {
                return redirect()->route('install.index');
            }

            if ($request->is('dashboard/uploads/chunked/*')) {
                return null;
            }

            if (
                $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof AuthorizationException
                || $e instanceof ModelNotFoundException
                || $e instanceof HttpExceptionInterface
                || config('app.debug')
            ) {
                return null;
            }

            return null;
        });
    })->create();
