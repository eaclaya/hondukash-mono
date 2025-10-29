<?php

namespace App\Http\Middleware;

use App\Models\AccountingConfiguration;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAccountingSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for setup wizard routes and auth routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Check if user is authenticated
        if (!auth('web')->check()) {
            return $next($request);
        }

        // Check if store setup is complete
        $hasStores = Store::exists();
        if (!$hasStores) {
            return redirect()->route('setup.store.index');
        }

        // Check if accounting configuration is complete
        $accountingConfig = AccountingConfiguration::first();
        if (!$accountingConfig || !$accountingConfig->is_configured) {
            return redirect()->route('setup.accounting.index');
        }

        return $next($request);
    }

    /**
     * Determine if the middleware should be skipped for this request.
     *
     * @param Request $request
     * @return bool
     */
    private function shouldSkip(Request $request): bool
    {
        $skipRoutes = [
            'setup.*',
            'login',
            'register',
            'logout',
            'tenant.login',
            'tenant.register',
            'tenant.logout',
        ];

        $skipPaths = [
            '/setup/*',
            '/login',
            '/register',
            '/logout',
        ];

        // Check route names
        $routeName = $request->route()?->getName();
        if ($routeName) {
            foreach ($skipRoutes as $pattern) {
                if (fnmatch($pattern, $routeName)) {
                    return true;
                }
            }
        }

        // Check paths
        $path = $request->path();
        foreach ($skipPaths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}