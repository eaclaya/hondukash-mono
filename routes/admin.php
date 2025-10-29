<?php

use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here are the routes for the admin/landlord functionality.
| These routes are loaded with the admin middleware and guard.
|
*/

// Admin Authentication Routes (Guest)
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('admin.register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Admin Authenticated Routes
Route::middleware('auth:admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');
    
    Route::get('/dashboard', function () {
        return Inertia::render('admin/dashboard', [
            'admin' => auth('admin')->user(),
        ]);
    })->name('admin.dashboard');
    
    // Admin root redirect
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });
});