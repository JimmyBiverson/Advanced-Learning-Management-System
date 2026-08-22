<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateEmailVerificationSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        if (app()->environment('local') && $this->hasValidConfiguredHostSignature($request)) {
            return $next($request);
        }

        abort(403, 'Invalid signature.');
    }

    private function hasValidConfiguredHostSignature(Request $request): bool
    {
        $configuredUrl = parse_url((string) config('app.url'));
        $host = $configuredUrl['host'] ?? null;

        if ($host === null) {
            return false;
        }

        $port = $configuredUrl['port'] ?? null;
        $configuredHost = $port === null ? $host : $host . ':' . $port;
        $alternateRequest = $request->duplicate();
        $alternateRequest->headers->set('host', $configuredHost);
        $alternateRequest->server->set('HTTP_HOST', $configuredHost);

        return $alternateRequest->hasValidSignature();
    }
}