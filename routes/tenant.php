<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    
    // Tenant Authentication Routes (Guest)
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('tenant.login');
        Route::post('/login', [AuthController::class, 'login']);
        
        Route::get('/register', [AuthController::class, 'showRegister'])->name('tenant.register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    // Tenant Authenticated Routes
    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('tenant.logout');
        
        Route::get('/dashboard', function () {
            return Inertia::render('tenant/dashboard', [
                'user' => auth('web')->user(),
                'tenant' => tenant(),
            ]);
        })->name('tenant.dashboard');
        
        // Include main application routes for tenants
        require __DIR__.'/web.php';
        
        // Include settings routes for tenants
        require __DIR__.'/settings.php';
    });
    
    // Tenant root route
    Route::get('/', function () {
        if (auth('web')->check()) {
            return redirect()->route('tenant.dashboard');
        }
        
        return Inertia::render('tenant/welcome', [
            'tenant' => tenant(),
            'canLogin' => true,
            'canRegister' => true,
        ]);
    })->name('tenant.home');
});
