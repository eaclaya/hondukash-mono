<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stancl\Tenancy\Database\Models\Domain;

class PreventTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * Prevents access to admin panel from tenant domains.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        
        // Check if the current domain is a tenant domain
        if (Domain::where('domain', $host)->exists()) {
            abort(404, 'Admin panel not accessible from tenant domains.');
        }
        
        // Check if the current domain is in the central domains list
        $centralDomains = config('tenancy.central_domains', []);
        
        if (!in_array($host, $centralDomains) && !in_array($request->getHttpHost(), $centralDomains)) {
            abort(404, 'Admin panel only accessible from central domains.');
        }
        
        return $next($request);
    }
}
