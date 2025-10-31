<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AccountingConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CompanyController extends Controller
{
    /**
     * Display the company settings.
     */
    public function index()
    {
        $accountingConfig = AccountingConfiguration::first();
        
        return Inertia::render('settings/company', [
            'company' => [
                'name' => $accountingConfig?->company_name,
                'registration' => $accountingConfig?->company_registration,
                'legal_form' => $accountingConfig?->legal_form,
                'address' => $accountingConfig?->company_address,
                'tax_number' => $accountingConfig?->tax_number,
            ],
            'tenant' => tenant(),
            'legalForms' => $this->getLegalForms(),
        ]);
    }

    /**
     * Update the company settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_registration' => 'nullable|string|max:50',
            'legal_form' => ['nullable', 'string', Rule::in(array_keys($this->getLegalForms()))],
            'company_address' => 'nullable|string|max:1000',
            'tax_number' => 'nullable|string|max:50',
        ]);

        $accountingConfig = AccountingConfiguration::first();
        
        if (!$accountingConfig) {
            return redirect()->back()->withErrors(['error' => 'Accounting configuration not found. Please run the setup wizard first.']);
        }

        $accountingConfig->update([
            'company_name' => $validated['company_name'],
            'company_registration' => $validated['company_registration'],
            'legal_form' => $validated['legal_form'],
            'company_address' => $validated['company_address'],
            'tax_number' => $validated['tax_number'],
        ]);

        return redirect()->back()->with('success', 'Company information updated successfully.');
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