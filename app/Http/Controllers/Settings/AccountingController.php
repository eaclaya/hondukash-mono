<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AccountingConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountingController extends Controller
{
    /**
     * Display the accounting configuration settings.
     */
    public function index()
    {
        $accountingConfig = AccountingConfiguration::first();
        
        return Inertia::render('settings/accounting', [
            'accountingConfig' => $accountingConfig,
            'currencies' => $this->getSupportedCurrencies(),
            'accountingMethods' => $this->getAccountingMethods(),
            'legalForms' => $this->getLegalForms(),
        ]);
    }

    /**
     * Update the accounting configuration.
     */
    public function update(Request $request)
    {
        $accountingConfig = AccountingConfiguration::first();
        
        if (!$accountingConfig) {
            return redirect()->back()->withErrors(['error' => 'Accounting configuration not found. Please run the setup wizard first.']);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_registration' => 'nullable|string|max:50',
            'legal_form' => ['nullable', 'string', Rule::in(array_keys($this->getLegalForms()))],
            'company_address' => 'nullable|string|max:1000',
            'fiscal_year_start' => 'required|string|regex:/^\d{2}-\d{2}$/',
            'accounting_method' => ['required', 'string', Rule::in(array_keys($this->getAccountingMethods()))],
            'base_currency' => ['required', 'string', 'max:3', Rule::in(array_keys($this->getSupportedCurrencies()))],
            'multi_currency_enabled' => 'boolean',
            'enabled_currencies' => 'nullable|array',
            'enabled_currencies.*' => Rule::in(array_keys($this->getSupportedCurrencies())),
            'tax_rate' => 'required|numeric|min:0|max:1',
            'tax_number' => 'nullable|string|max:50',
            'tax_inclusive_pricing' => 'boolean',
            'use_departments' => 'boolean',
            'use_cost_centers' => 'boolean',
            'use_projects' => 'boolean',
            'invoice_numbering_pattern' => 'required|string|max:50',
            'receipt_numbering_pattern' => 'required|string|max:50',
        ]);

        $accountingConfig->update($validated);

        return redirect()->back()->with('success', 'Accounting configuration updated successfully.');
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
     * Get accounting methods.
     */
    private function getAccountingMethods(): array
    {
        return [
            'accrual' => 'Accrual Accounting (Recommended)',
            'cash' => 'Cash Accounting',
        ];
    }

    /**
     * Get legal forms for Honduras.
     */
    private function getLegalForms(): array
    {
        return [
            'SA' => 'Sociedad Anónima (S.A.)',
            'SRL' => 'Sociedad de Responsabilidad Limitada (S.R.L.)',
            'EIRL' => 'Empresa Individual de Responsabilidad Limitada (E.I.R.L.)',
            'NATURAL' => 'Persona Natural',
            'COMERCIANTE' => 'Comerciante Individual',
            'OTHER' => 'Other',
        ];
    }
}