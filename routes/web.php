<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Central Domain Routes
|--------------------------------------------------------------------------
|
| These routes are for the central/landlord domain functionality.
| They handle the main application and admin access.
|
*/

// Central domain routes (for landlord/admin access)
Route::domain(config('tenancy.central_domains.0', 'localhost'))->group(function () {
    Route::get('/', function () {
        return Inertia::render('welcome', [
            'canLogin' => true,
            'adminUrl' => url('/admin'),
        ]);
    })->name('home');
});

// Default application routes (used by tenant routes when included)
Route::middleware(['auth:web'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard', [
            'user' => auth('web')->user(),
        ]);
    })->name('dashboard');
});
