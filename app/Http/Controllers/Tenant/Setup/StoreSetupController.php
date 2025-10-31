<?php

namespace App\Http\Controllers\Tenant\Setup;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StoreSetupController extends Controller
{
    /**
     * Show the store setup wizard.
     */
    public function index()
    {
        return Inertia::render('tenant/setup/store', [
            'tenant' => tenant(),
            'currencies' => $this->getSupportedCurrencies(),
            'timezones' => $this->getSupportedTimezones(),
            'countries' => $this->getSupportedCountries(),
        ]);
    }

    /**
     * Store the new store configuration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:stores,code',
            'type' => ['required', Rule::in(['store', 'warehouse'])],
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:2',
            'postal_code' => 'nullable|string|max:20',
            'tax_rate' => 'nullable|numeric|min:0|max:1',
            'currency' => ['required', 'string', 'max:3', Rule::in(array_keys($this->getSupportedCurrencies()))],
            'timezone' => ['required', 'string', Rule::in(array_keys($this->getSupportedTimezones()))],
        ]);

        // Prepare address JSON
        $address = [
            'street' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
        ];

        // Prepare settings JSON
        $settings = [
            'tax_rate' => $validated['tax_rate'] ?? 0.15,
            'currency' => $validated['currency'],
            'timezone' => $validated['timezone'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'is_default' => true, // First store is default
        ];

        // Create the store
        $store = Store::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['type'],
            'address' => $address,
            'settings' => $settings,
            'is_active' => true,
        ]);

        \Log::info('Store created successfully', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'tenant_id' => tenant()?->id,
            'redirect_to' => route('setup.accounting.index')
        ]);

        return redirect()->route('setup.accounting.index')->with('success', 'Store created successfully! Now let\'s configure your accounting settings.');
    }

    /**
     * Get supported currencies.
     */
    private function getSupportedCurrencies(): array
    {
        return [
            'HNL' => 'Honduran Lempira (L)',
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GTQ' => 'Guatemalan Quetzal (Q)',
            'CRC' => 'Costa Rican Colón (₡)',
            'NIO' => 'Nicaraguan Córdoba (C$)',
        ];
    }

    /**
     * Get supported timezones.
     */
    private function getSupportedTimezones(): array
    {
        return [
            'America/Tegucigalpa' => 'Honduras (UTC-6)',
            'America/Guatemala' => 'Guatemala (UTC-6)',
            'America/Costa_Rica' => 'Costa Rica (UTC-6)',
            'America/Managua' => 'Nicaragua (UTC-6)',
            'America/New_York' => 'Eastern Time (UTC-5/-4)',
            'America/Chicago' => 'Central Time (UTC-6/-5)',
            'America/Denver' => 'Mountain Time (UTC-7/-6)',
            'America/Los_Angeles' => 'Pacific Time (UTC-8/-7)',
        ];
    }

    /**
     * Get supported countries.
     */
    private function getSupportedCountries(): array
    {
        return [
            'HN' => 'Honduras',
            'US' => 'United States',
            'GT' => 'Guatemala',
            'CR' => 'Costa Rica',
            'NI' => 'Nicaragua',
            'SV' => 'El Salvador',
            'BZ' => 'Belize',
            'MX' => 'Mexico',
        ];
    }
}