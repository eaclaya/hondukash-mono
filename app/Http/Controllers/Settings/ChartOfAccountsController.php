<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant\AccountingConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ChartOfAccountsController extends Controller
{
    /**
     * Display the chart of accounts settings.
     */
    public function index()
    {
        $accountingConfig = AccountingConfiguration::first();
        
        return Inertia::render('settings/chart-of-accounts', [
            'chartOfAccounts' => $accountingConfig?->chart_of_accounts ?? [],
            'accountNumberingScheme' => $accountingConfig?->account_numbering_scheme ?? [],
            'accountTypes' => $this->getAccountTypes(),
        ]);
    }

    /**
     * Store a new account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(array_keys($this->getAccountTypes()))],
            'parent_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        $accountingConfig = AccountingConfiguration::first();
        if (!$accountingConfig) {
            return redirect()->back()->withErrors(['error' => 'Accounting configuration not found.']);
        }

        $chartOfAccounts = $accountingConfig->chart_of_accounts ?? [];
        
        // Check if code already exists
        if (isset($chartOfAccounts[$validated['code']])) {
            return redirect()->back()->withErrors(['code' => 'Account code already exists.']);
        }

        // Add new account
        $chartOfAccounts[$validated['code']] = [
            'name' => $validated['name'],
            'type' => $validated['type'],
            'parent_code' => $validated['parent_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ];

        $accountingConfig->update(['chart_of_accounts' => $chartOfAccounts]);

        return redirect()->back()->with('success', 'Account created successfully.');
    }

    /**
     * Update an existing account.
     */
    public function update(Request $request, string $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(array_keys($this->getAccountTypes()))],
            'parent_code' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $accountingConfig = AccountingConfiguration::first();
        if (!$accountingConfig) {
            return redirect()->back()->withErrors(['error' => 'Accounting configuration not found.']);
        }

        $chartOfAccounts = $accountingConfig->chart_of_accounts ?? [];
        
        if (!isset($chartOfAccounts[$account])) {
            return redirect()->back()->withErrors(['error' => 'Account not found.']);
        }

        // Update account
        $chartOfAccounts[$account] = array_merge($chartOfAccounts[$account], $validated);

        $accountingConfig->update(['chart_of_accounts' => $chartOfAccounts]);

        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    /**
     * Delete an account.
     */
    public function destroy(string $account)
    {
        $accountingConfig = AccountingConfiguration::first();
        if (!$accountingConfig) {
            return redirect()->back()->withErrors(['error' => 'Accounting configuration not found.']);
        }

        $chartOfAccounts = $accountingConfig->chart_of_accounts ?? [];
        
        if (!isset($chartOfAccounts[$account])) {
            return redirect()->back()->withErrors(['error' => 'Account not found.']);
        }

        // Check if account has children (is a parent)
        $hasChildren = false;
        foreach ($chartOfAccounts as $code => $accountData) {
            if (isset($accountData['parent_code']) && $accountData['parent_code'] === $account) {
                $hasChildren = true;
                break;
            }
        }

        if ($hasChildren) {
            return redirect()->back()->withErrors(['error' => 'Cannot delete account that has child accounts.']);
        }

        // Remove account
        unset($chartOfAccounts[$account]);

        $accountingConfig->update(['chart_of_accounts' => $chartOfAccounts]);

        return redirect()->back()->with('success', 'Account deleted successfully.');
    }

    /**
     * Get account types.
     */
    private function getAccountTypes(): array
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'revenue' => 'Revenue',
            'expense' => 'Expense',
        ];
    }
}