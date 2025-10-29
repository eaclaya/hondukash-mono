<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus(),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(),
            ]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        // 🎯 INTERCEPT PAYLOAD HERE - Log all login attempts
        Log::info('🔐 Filament Admin Login Attempt', [
            'email' => $data['email'] ?? 'N/A',
            'has_password' => !empty($data['password']),
            'password_length' => isset($data['password']) ? strlen($data['password']) : 0,
            'guard' => 'admin',
            'connection' => 'central',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ]);

        // Validate the admin user exists before attempting authentication
        $admin = \App\Models\Admin::where('email', $data['email'])->first();

        if (!$admin) {
            Log::warning('🚫 Admin user not found', [
                'email' => $data['email'],
                'total_admins' => \App\Models\Admin::count(),
            ]);
        } else {
            Log::info('✅ Admin user found', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'admin_email' => $admin->email,
                'created_at' => $admin->created_at,
            ]);
        }

        return parent::getCredentialsFromFormData($data);
    }

    protected function throwFailureValidationException(): never
    {
        // 🎯 INTERCEPT FAILED LOGIN HERE
        Log::warning('❌ Filament Admin Login Failed', [
            'guard' => 'admin',
            'ip_address' => request()->ip(),
            'form_data' => $this->form->getState(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ]);

        // Check common failure reasons
        $this->logAuthenticationDiagnostics();

        parent::throwFailureValidationException();
    }

    /**
     * Log detailed authentication diagnostics for debugging
     */
    private function logAuthenticationDiagnostics(): void
    {
        $formData = $this->form->getState();
        $email = $formData['email'] ?? null;

        Log::info('🔍 Authentication Diagnostics', [
            'guard_config' => config('auth.guards.admin'),
            'provider_config' => config('auth.providers.admins'),
            'admin_model_connection' => (new \App\Models\Admin())->getConnectionName(),
            'database_connections' => array_keys(config('database.connections')),
            'session_driver' => config('session.driver'),
            'admin_table_exists' => \Schema::connection('central')->hasTable('admins'),
            'total_admins_count' => \App\Models\Admin::count(),
        ]);

        if ($email) {
            // Check if user exists and password verification
            $admin = \App\Models\Admin::where('email', $email)->first();

            if ($admin && isset($formData['password'])) {
                $passwordCheck = \Hash::check($formData['password'], $admin->password);

                Log::info('🔐 Password Verification', [
                    'email' => $email,
                    'user_exists' => true,
                    'password_matches' => $passwordCheck,
                    'stored_password_length' => strlen($admin->password),
                    'password_starts_with' => substr($admin->password, 0, 10),
                ]);
            }
        }
    }

    public function authenticate(): \Filament\Http\Responses\Auth\Contracts\LoginResponse|null
    {
        Log::info('🚀 Starting Filament authentication process', [
            'guard' => 'admin',
            'form_valid' => $this->form->getState() !== null,
        ]);

        try {
            $result = parent::authenticate();

            Log::info('✅ Filament authentication successful', [
                'redirect_url' => $result,
                'authenticated_user' => auth('admin')->user()?->email,
                'session_regenerated' => true,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('💥 Filament authentication exception', [
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
            ]);

            throw $e;
        }
    }
}
