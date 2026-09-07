<?php

namespace Modules\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstalledRoutes
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (isInstallerRequest($request) || ! isDBConnected()) {
            config(['session.driver' => 'file']);
        }

        $installed = Storage::disk('public')->exists('installed') || env('MENTOR_INSTALLED');

        if ($installed) {
            return $next($request);
        }

        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}
