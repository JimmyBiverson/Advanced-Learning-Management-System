<?php

namespace Modules\Installer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

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

        if (applicationInstalled()) {
            return $next($request);
        }

        if ($request->is('install', 'install/*')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}
