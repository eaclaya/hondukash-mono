<?php

namespace App\Http\Middleware;

use App\Models\Tenant\AccountingConfiguration;
use App\Models\Tenant\Store;
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
        \Log::info('RequireAccountingSetup middleware called', [
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
            'user_authenticated' => auth('web')->check(),
            'tenant_id' => tenant()?->id,
        ]);

        // Skip middleware for setup wizard routes and auth routes
        if ($this->shouldSkip($request)) {
            \Log::info('RequireAccountingSetup middleware skipped', [
                'reason' => 'setup route',
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        // Check if user is authenticated
        if (!auth('web')->check()) {
            \Log::info('RequireAccountingSetup middleware: user not authenticated');
            return $next($request);
        }

        // Check if store setup is complete
        $hasStores = Store::exists();
        \Log::info('RequireAccountingSetup middleware: store check', [
            'has_stores' => $hasStores,
            'stores_count' => Store::count(),
        ]);
        
        if (!$hasStores) {
            \Log::info('RequireAccountingSetup middleware: redirecting to store setup');
            return redirect()->route('setup.store.index');
        }

        // Check if accounting configuration is complete
        $accountingConfig = AccountingConfiguration::first();
        $isConfigured = $accountingConfig && $accountingConfig->is_configured;
        
        \Log::info('RequireAccountingSetup middleware: accounting check', [
            'has_config' => $accountingConfig !== null,
            'is_configured' => $isConfigured,
            'config_count' => AccountingConfiguration::count(),
        ]);
        
        if (!$accountingConfig || !$accountingConfig->is_configured) {
            \Log::info('RequireAccountingSetup middleware: redirecting to accounting setup');
            return redirect()->route('setup.accounting.index');
        }

        \Log::info('RequireAccountingSetup middleware: setup complete, proceeding');
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