<?php

namespace App\Services;

use App\Models\Tenant\Account;
use App\Models\Tenant\Client;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\JournalEntry;
use App\Models\Tenant\JournalEntryLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportingService
{
    /**
     * Generate Balance Sheet (Statement of Financial Position)
     */
    public function generateBalanceSheet(string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        
        $assets = Account::assets()->active()->get()->map(function ($account) use ($asOfDate) {
            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->full_name,
                'balance' => $account->getBalance(null, $asOfDate),
                'is_parent' => $account->is_parent,
                'parent_id' => $account->parent_id,
            ];
        });

        $liabilities = Account::liabilities()->active()->get()->map(function ($account) use ($asOfDate) {
            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->full_name,
                'balance' => $account->getBalance(null, $asOfDate),
                'is_parent' => $account->is_parent,
                'parent_id' => $account->parent_id,
            ];
        });

        $equity = Account::equity()->active()->get()->map(function ($account) use ($asOfDate) {
            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->full_name,
                'balance' => $account->getBalance(null, $asOfDate),
                'is_parent' => $account->is_parent,
                'parent_id' => $account->parent_id,
            ];
        });

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance');

        return [
            'report_type' => 'balance_sheet',
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'assets' => [
                'accounts' => $assets->toArray(),
                'total' => $totalAssets,
            ],
            'liabilities' => [
                'accounts' => $liabilities->toArray(),
                'total' => $totalLiabilities,
            ],
            'equity' => [
                'accounts' => $equity->toArray(),
                'total' => $totalEquity,
            ],
            'totals' => [
                'total_assets' => $totalAssets,
                'total_liabilities_and_equity' => $totalLiabilities + $totalEquity,
                'balance_check' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            ],
        ];
    }

    /**
     * Generate Income Statement (Profit and Loss Statement)
     */
    public function generateIncomeStatement(string $startDate, string $endDate): array
    {
        $revenues = Account::revenue()->active()->get()->map(function ($account) use ($startDate, $endDate) {
            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->full_name,
                'balance' => $account->getBalance($startDate, $endDate),
                'is_parent' => $account->is_parent,
                'parent_id' => $account->parent_id,
            ];
        });

        $expenses = Account::expense()->active()->get()->map(function ($account) use ($startDate, $endDate) {
            return [
                'account_id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_name' => $account->full_name,
                'balance' => $account->getBalance($startDate, $endDate),
                'is_parent' => $account->is_parent,
                'parent_id' => $account->parent_id,
            ];
        });

        $totalRevenue = $revenues->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $netIncome = $totalRevenue - $totalExpenses;

        return [
            'report_type' => 'income_statement',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_at' => now(),
            'revenue' => [
                'accounts' => $revenues->toArray(),
                'total' => $totalRevenue,
            ],
            'expenses' => [
                'accounts' => $expenses->toArray(),
                'total' => $totalExpenses,
            ],
            'summary' => [
                'gross_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
                'profit_margin' => $totalRevenue > 0 ? ($netIncome / $totalRevenue) * 100 : 0,
            ],
        ];
    }

    /**
     * Generate Cash Flow Statement
     */
    public function generateCashFlowStatement(string $startDate, string $endDate): array
    {
        $cashAccounts = Account::cashAccounts()->active()->get();
        
        $operatingActivities = $this->getCashFlowByCategory($startDate, $endDate, 'operating');
        $investingActivities = $this->getCashFlowByCategory($startDate, $endDate, 'investing');
        $financingActivities = $this->getCashFlowByCategory($startDate, $endDate, 'financing');

        // Calculate beginning and ending cash balances
        $beginningCash = $cashAccounts->sum(function ($account) use ($startDate) {
            return $account->getBalance(null, Carbon::parse($startDate)->subDay());
        });

        $endingCash = $cashAccounts->sum(function ($account) use ($endDate) {
            return $account->getBalance(null, $endDate);
        });

        $netOperatingCash = $operatingActivities->sum('amount');
        $netInvestingCash = $investingActivities->sum('amount');
        $netFinancingCash = $financingActivities->sum('amount');
        $netCashIncrease = $netOperatingCash + $netInvestingCash + $netFinancingCash;

        return [
            'report_type' => 'cash_flow_statement',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_at' => now(),
            'operating_activities' => [
                'activities' => $operatingActivities->toArray(),
                'net_cash_from_operating' => $netOperatingCash,
            ],
            'investing_activities' => [
                'activities' => $investingActivities->toArray(),
                'net_cash_from_investing' => $netInvestingCash,
            ],
            'financing_activities' => [
                'activities' => $financingActivities->toArray(),
                'net_cash_from_financing' => $netFinancingCash,
            ],
            'summary' => [
                'beginning_cash' => $beginningCash,
                'net_cash_increase' => $netCashIncrease,
                'ending_cash' => $endingCash,
                'calculated_ending_cash' => $beginningCash + $netCashIncrease,
                'reconciliation_difference' => $endingCash - ($beginningCash + $netCashIncrease),
            ],
        ];
    }

    /**
     * Generate Accounts Receivable Aging Report
     */
    public function generateARAgingReport(string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        
        $clients = Client::active()
            ->whereHas('invoices', function ($query) {
                $query->whereIn('status', ['sent', 'overdue']);
            })
            ->get();

        $agingData = [];
        $totals = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'total_outstanding' => 0,
        ];

        foreach ($clients as $client) {
            $clientAging = $client->getAgingReport($asOfDate);
            
            if ($clientAging['total_outstanding'] > 0) {
                $agingData[] = [
                    'client_id' => $client->id,
                    'client_code' => $client->code,
                    'client_name' => $client->name,
                    'client_type' => $client->type,
                    'credit_limit' => $client->credit_limit,
                    'aging' => $clientAging,
                ];

                foreach (['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_over_90', 'total_outstanding'] as $bucket) {
                    $totals[$bucket] += $clientAging[$bucket];
                }
            }
        }

        return [
            'report_type' => 'ar_aging',
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'clients' => $agingData,
            'summary' => $totals,
            'aging_buckets' => [
                'current' => 'Current (0 days)',
                'days_1_30' => '1-30 days past due',
                'days_31_60' => '31-60 days past due',
                'days_61_90' => '61-90 days past due',
                'days_over_90' => 'Over 90 days past due',
            ],
        ];
    }

    /**
     * Generate Accounts Payable Aging Report
     */
    public function generateAPAgingReport(string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        
        $suppliers = Supplier::active()
            ->where(function ($query) {
                $query->whereHas('purchaseOrders', function ($q) {
                    $q->whereIn('status', ['approved', 'received', 'partial']);
                })->orWhereExists(function ($q) {
                    $q->selectRaw('1')
                      ->from('expenses')
                      ->whereColumn('expenses.vendor_name', 'suppliers.name')
                      ->whereIn('expenses.status', ['pending', 'approved']);
                });
            })
            ->get();

        $agingData = [];
        $totals = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_over_90' => 0,
            'total_outstanding' => 0,
        ];

        foreach ($suppliers as $supplier) {
            $supplierAging = $supplier->getAgingReport($asOfDate);
            
            if ($supplierAging['total_outstanding'] > 0) {
                $agingData[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_code' => $supplier->code,
                    'supplier_name' => $supplier->display_name,
                    'payment_terms' => $supplier->payment_terms,
                    'credit_limit' => $supplier->credit_limit,
                    'aging' => $supplierAging,
                ];

                foreach (['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_over_90', 'total_outstanding'] as $bucket) {
                    $totals[$bucket] += $supplierAging[$bucket];
                }
            }
        }

        return [
            'report_type' => 'ap_aging',
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'suppliers' => $agingData,
            'summary' => $totals,
            'aging_buckets' => [
                'current' => 'Current (not yet due)',
                'days_1_30' => '1-30 days past due',
                'days_31_60' => '31-60 days past due',
                'days_61_90' => '61-90 days past due',
                'days_over_90' => 'Over 90 days past due',
            ],
        ];
    }

    /**
     * Generate General Ledger Report
     */
    public function generateGeneralLedger(
        string $startDate,
        string $endDate,
        string $accountId = null
    ): array {
        $accountsQuery = Account::active();
        
        if ($accountId) {
            $accountsQuery->where('id', $accountId);
        }
        
        $accounts = $accountsQuery->get();
        $ledgerData = [];

        foreach ($accounts as $account) {
            $beginningBalance = $account->getBalance(null, Carbon::parse($startDate)->subDay());
            
            $entries = $account->journalEntryLines()
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted')
                      ->whereBetween('entry_date', [$startDate, $endDate]);
                })
                ->with(['journalEntry'])
                ->orderBy('created_at')
                ->get();

            $transactions = [];
            $runningBalance = $beginningBalance;

            foreach ($entries as $line) {
                $amount = $line->debit ?: -$line->credit;
                
                if ($account->has_credit_balance) {
                    $amount = -$amount;
                }
                
                $runningBalance += $amount;
                
                $transactions[] = [
                    'date' => $line->journalEntry->entry_date,
                    'description' => $line->description ?: $line->journalEntry->description,
                    'reference_type' => $line->journalEntry->reference_type,
                    'reference_id' => $line->journalEntry->reference_id,
                    'debit' => $line->debit,
                    'credit' => $line->credit,
                    'running_balance' => $runningBalance,
                ];
            }

            $endingBalance = $account->getBalance(null, $endDate);

            $ledgerData[] = [
                'account_id' => $account->id,
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'beginning_balance' => $beginningBalance,
                'ending_balance' => $endingBalance,
                'total_debits' => $entries->sum('debit'),
                'total_credits' => $entries->sum('credit'),
                'transaction_count' => $entries->count(),
                'transactions' => $transactions,
            ];
        }

        return [
            'report_type' => 'general_ledger',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'generated_at' => now(),
            'account_filter' => $accountId,
            'accounts' => $ledgerData,
            'summary' => [
                'total_accounts' => count($ledgerData),
                'total_transactions' => array_sum(array_column($ledgerData, 'transaction_count')),
                'total_debits' => array_sum(array_column($ledgerData, 'total_debits')),
                'total_credits' => array_sum(array_column($ledgerData, 'total_credits')),
            ],
        ];
    }

    /**
     * Get cash flow activities by category
     */
    private function getCashFlowByCategory(string $startDate, string $endDate, string $category): Collection
    {
        $journalEntries = JournalEntry::where('status', 'posted')
            ->where('affects_cash', true)
            ->where('cash_flow_category', $category)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->with(['lines.account'])
            ->get();

        $activities = collect();

        foreach ($journalEntries as $entry) {
            $cashLines = $entry->lines->filter(function ($line) {
                return $line->account->is_cash_account;
            });

            foreach ($cashLines as $line) {
                $amount = $line->account->has_debit_balance 
                    ? ($line->debit - $line->credit)
                    : ($line->credit - $line->debit);

                $activities->push([
                    'date' => $entry->entry_date,
                    'description' => $line->description ?: $entry->description,
                    'reference_type' => $entry->reference_type,
                    'reference_id' => $entry->reference_id,
                    'account_code' => $line->account->code,
                    'account_name' => $line->account->name,
                    'amount' => $amount,
                ]);
            }
        }

        return $activities->sortBy('date');
    }

    /**
     * Generate comprehensive financial dashboard data
     */
    public function generateFinancialDashboard(string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ?: now()->toDateString();
        $monthStart = Carbon::parse($asOfDate)->startOfMonth()->toDateString();
        $yearStart = Carbon::parse($asOfDate)->startOfYear()->toDateString();

        return [
            'as_of_date' => $asOfDate,
            'generated_at' => now(),
            'quick_stats' => [
                'total_assets' => Account::assets()->active()->get()->sum(function ($account) use ($asOfDate) {
                    return $account->getBalance(null, $asOfDate);
                }),
                'total_liabilities' => Account::liabilities()->active()->get()->sum(function ($account) use ($asOfDate) {
                    return $account->getBalance(null, $asOfDate);
                }),
                'total_equity' => Account::equity()->active()->get()->sum(function ($account) use ($asOfDate) {
                    return $account->getBalance(null, $asOfDate);
                }),
                'monthly_revenue' => Account::revenue()->active()->get()->sum(function ($account) use ($monthStart, $asOfDate) {
                    return $account->getBalance($monthStart, $asOfDate);
                }),
                'monthly_expenses' => Account::expense()->active()->get()->sum(function ($account) use ($monthStart, $asOfDate) {
                    return $account->getBalance($monthStart, $asOfDate);
                }),
                'ytd_revenue' => Account::revenue()->active()->get()->sum(function ($account) use ($yearStart, $asOfDate) {
                    return $account->getBalance($yearStart, $asOfDate);
                }),
                'ytd_expenses' => Account::expense()->active()->get()->sum(function ($account) use ($yearStart, $asOfDate) {
                    return $account->getBalance($yearStart, $asOfDate);
                }),
            ],
            'aging_summaries' => [
                'ar_aging' => $this->generateARAgingReport($asOfDate)['summary'],
                'ap_aging' => $this->generateAPAgingReport($asOfDate)['summary'],
            ],
        ];
    }
}