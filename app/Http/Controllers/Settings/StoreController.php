<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class StoreController extends Controller
{
    /**
     * Display the stores management.
     */
    public function index()
    {
        $stores = Store::orderBy('name')->get();
        
        return Inertia::render('settings/stores', [
            'stores' => $stores,
            'currencies' => $this->getSupportedCurrencies(),
            'timezones' => $this->getSupportedTimezones(),
            'countries' => $this->getSupportedCountries(),
        ]);
    }

    /**
     * Store a new store.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:stores,code',
            'type' => 'required|in:store,warehouse',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:2',
            'postal_code' => 'nullable|string|max:20',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'currency' => ['required', 'string', 'max:3', Rule::in(array_keys($this->getSupportedCurrencies()))],
            'timezone' => ['required', 'string', Rule::in(array_keys($this->getSupportedTimezones()))],
            'is_active' => 'boolean',
        ]);

        // Transform address fields into address array
        $addressData = [
            'street' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'],
            'postal_code' => $validated['postal_code'] ?? null,
        ];

        // Transform settings
        $settingsData = [
            'tax_rate' => $validated['tax_rate'],
            'currency' => $validated['currency'],
            'timezone' => $validated['timezone'],
        ];

        Store::create([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $addressData,
            'settings' => $settingsData,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Store created successfully.');
    }

    /**
     * Update an existing store.
     */
    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:10', Rule::unique('stores', 'code')->ignore($store->id)],
            'type' => 'required|in:store,warehouse',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'required|string|max:2',
            'postal_code' => 'nullable|string|max:20',
            'tax_rate' => 'required|numeric|min:0|max:1',
            'currency' => ['required', 'string', 'max:3', Rule::in(array_keys($this->getSupportedCurrencies()))],
            'timezone' => ['required', 'string', Rule::in(array_keys($this->getSupportedTimezones()))],
            'is_active' => 'boolean',
        ]);

        // Transform address fields into address array
        $addressData = [
            'street' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'],
            'postal_code' => $validated['postal_code'] ?? null,
        ];

        // Transform settings
        $settingsData = [
            'tax_rate' => $validated['tax_rate'],
            'currency' => $validated['currency'],
            'timezone' => $validated['timezone'],
        ];

        $store->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
            'type' => $validated['type'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $addressData,
            'settings' => $settingsData,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->back()->with('success', 'Store updated successfully.');
    }

    /**
     * Delete a store.
     */
    public function destroy(Store $store)
    {
        // Check if this is the last store
        if (Store::count() <= 1) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete the last store. At least one store is required.']);
        }

        $store->delete();

        return redirect()->back()->with('success', 'Store deleted successfully.');
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
            'America/Tegucigalpa' => 'Central Time (Tegucigalpa)',
            'America/Guatemala' => 'Central Time (Guatemala)',
            'America/Costa_Rica' => 'Central Time (Costa Rica)',
            'America/Managua' => 'Central Time (Managua)',
            'America/New_York' => 'Eastern Time',
            'America/Chicago' => 'Central Time (US)',
            'America/Denver' => 'Mountain Time',
            'America/Los_Angeles' => 'Pacific Time',
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