<?php

namespace App\Providers;

use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class AuthEventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        //
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // Listen for authentication attempt events
        Event::listen(Attempting::class, function (Attempting $event) {
            Log::info('🎯 Authentication Attempt Event', [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? 'N/A',
                'has_password' => !empty($event->credentials['password']),
                'remember' => $event->remember,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'session_id' => session()->getId(),
            ]);
        });

        // Listen for successful authentication events
        Event::listen(Authenticated::class, function (Authenticated $event) {
            Log::info('✅ Authentication Success Event', [
                'guard' => $event->guard,
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'user_name' => $event->user->name,
                'user_model' => get_class($event->user),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
                'session_id' => session()->getId(),
            ]);
        });

        // Listen for authentication failure events
        Event::listen(Failed::class, function (Failed $event) {
            Log::warning('❌ Authentication Failed Event', [
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? 'N/A',
                'user_exists' => $event->user !== null,
                'user_id' => $event->user?->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
                'session_id' => session()->getId(),
            ]);

            // Additional debugging for admin guard failures
            if ($event->guard === 'admin' && isset($event->credentials['email'])) {
                $this->logAdminAuthFailureDetails($event->credentials['email']);
            }
        });

        // Listen for login events
        Event::listen(Login::class, function (Login $event) {
            Log::info('🚀 Login Event', [
                'guard' => $event->guard,
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
                'remember' => $event->remember,
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
                'session_regenerated' => true,
            ]);
        });

        // Listen for logout events
        Event::listen(Logout::class, function (Logout $event) {
            Log::info('👋 Logout Event', [
                'guard' => $event->guard,
                'user_id' => $event->user?->id,
                'user_email' => $event->user?->email,
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
                'session_invalidated' => true,
            ]);
        });
    }

    /**
     * Log detailed admin authentication failure information
     */
    private function logAdminAuthFailureDetails(string $email): void
    {
        try {
            $admin = \App\Models\Admin::where('email', $email)->first();
            
            Log::info('🔍 Admin Auth Failure Details', [
                'email' => $email,
                'admin_exists' => $admin !== null,
                'admin_id' => $admin?->id,
                'admin_created_at' => $admin?->created_at,
                'total_admins' => \App\Models\Admin::count(),
                'admins_table_exists' => \Schema::connection('central')->hasTable('admins'),
                'guard_config' => config('auth.guards.admin'),
                'provider_config' => config('auth.providers.admins'),
                'model_connection' => (new \App\Models\Admin())->getConnectionName(),
            ]);

            // Check if there are any admins at all
            $adminCount = \App\Models\Admin::count();
            if ($adminCount === 0) {
                Log::warning('⚠️ No admins found in database - run admin seeder?');
            }

        } catch (\Exception $e) {
            Log::error('💥 Error logging admin auth failure details', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}