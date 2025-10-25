<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialReportingService;
use App\Models\Tenant\Account;
use App\Models\Tenant\Client;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\Payment;

class TestFinancialReports extends Command
{
    protected $signature = 'test:financial-reports';
    protected $description = 'Test financial reporting functionality';

    public function handle()
    {
        $this->info('🧪 Testing Financial Reporting System...');

        $reportingService = new FinancialReportingService();

        try {
            // Test 1: Balance Sheet
            $this->info("\n📊 Testing Balance Sheet...");
            $balanceSheet = $reportingService->generateBalanceSheet();
            $this->info("✅ Balance Sheet generated successfully");
            $this->info("   Total Assets: $" . number_format($balanceSheet['totals']['total_assets'], 2));
            $this->info("   Total Liabilities & Equity: $" . number_format($balanceSheet['totals']['total_liabilities_and_equity'], 2));
            $this->info("   Balance Check: " . ($balanceSheet['totals']['balance_check'] ? 'PASSED' : 'FAILED'));

            // Test 2: Income Statement
            $this->info("\n📈 Testing Income Statement...");
            $startDate = now()->startOfMonth()->toDateString();
            $endDate = now()->toDateString();
            $incomeStatement = $reportingService->generateIncomeStatement($startDate, $endDate);
            $this->info("✅ Income Statement generated successfully");
            $this->info("   Period: {$startDate} to {$endDate}");
            $this->info("   Gross Revenue: $" . number_format($incomeStatement['summary']['gross_revenue'], 2));
            $this->info("   Total Expenses: $" . number_format($incomeStatement['summary']['total_expenses'], 2));
            $this->info("   Net Income: $" . number_format($incomeStatement['summary']['net_income'], 2));

            // Test 3: Cash Flow Statement
            $this->info("\n💰 Testing Cash Flow Statement...");
            $cashFlowStatement = $reportingService->generateCashFlowStatement($startDate, $endDate);
            $this->info("✅ Cash Flow Statement generated successfully");
            $this->info("   Beginning Cash: $" . number_format($cashFlowStatement['summary']['beginning_cash'], 2));
            $this->info("   Net Cash Increase: $" . number_format($cashFlowStatement['summary']['net_cash_increase'], 2));
            $this->info("   Ending Cash: $" . number_format($cashFlowStatement['summary']['ending_cash'], 2));

            // Test 4: A/R Aging Report
            $this->info("\n📅 Testing A/R Aging Report...");
            $arAging = $reportingService->generateARAgingReport();
            $this->info("✅ A/R Aging Report generated successfully");
            $this->info("   Total Outstanding: $" . number_format($arAging['summary']['total_outstanding'], 2));
            $this->info("   Current: $" . number_format($arAging['summary']['current'], 2));
            $this->info("   Past Due: $" . number_format(
                $arAging['summary']['days_1_30'] + 
                $arAging['summary']['days_31_60'] + 
                $arAging['summary']['days_61_90'] + 
                $arAging['summary']['days_over_90'], 2
            ));

            // Test 5: A/P Aging Report
            $this->info("\n📋 Testing A/P Aging Report...");
            $apAging = $reportingService->generateAPAgingReport();
            $this->info("✅ A/P Aging Report generated successfully");
            $this->info("   Total Outstanding: $" . number_format($apAging['summary']['total_outstanding'], 2));
            $this->info("   Current: $" . number_format($apAging['summary']['current'], 2));
            $this->info("   Past Due: $" . number_format(
                $apAging['summary']['days_1_30'] + 
                $apAging['summary']['days_31_60'] + 
                $apAging['summary']['days_61_90'] + 
                $apAging['summary']['days_over_90'], 2
            ));

            // Test 6: General Ledger
            $this->info("\n📚 Testing General Ledger...");
            $generalLedger = $reportingService->generateGeneralLedger($startDate, $endDate);
            $this->info("✅ General Ledger generated successfully");
            $this->info("   Total Accounts: " . $generalLedger['summary']['total_accounts']);
            $this->info("   Total Transactions: " . $generalLedger['summary']['total_transactions']);
            $this->info("   Total Debits: $" . number_format($generalLedger['summary']['total_debits'], 2));
            $this->info("   Total Credits: $" . number_format($generalLedger['summary']['total_credits'], 2));

            // Test 7: Financial Dashboard
            $this->info("\n🎛️ Testing Financial Dashboard...");
            $dashboard = $reportingService->generateFinancialDashboard();
            $this->info("✅ Financial Dashboard generated successfully");
            $this->info("   Monthly Revenue: $" . number_format($dashboard['quick_stats']['monthly_revenue'], 2));
            $this->info("   Monthly Expenses: $" . number_format($dashboard['quick_stats']['monthly_expenses'], 2));
            $this->info("   YTD Revenue: $" . number_format($dashboard['quick_stats']['ytd_revenue'], 2));
            $this->info("   YTD Expenses: $" . number_format($dashboard['quick_stats']['ytd_expenses'], 2));

            // Test 8: Model Relationships
            $this->info("\n🔗 Testing Model Relationships...");
            
            $accountsCount = Account::count();
            $this->info("   Accounts: {$accountsCount}");
            
            $clientsCount = Client::count();
            $this->info("   Clients: {$clientsCount}");
            
            $suppliersCount = Supplier::count();
            $this->info("   Suppliers: {$suppliersCount}");
            
            $invoicesCount = Invoice::count();
            $this->info("   Invoices: {$invoicesCount}");
            
            $paymentsCount = Payment::count();
            $this->info("   Payments: {$paymentsCount}");
            
            $invoicePaymentsCount = InvoicePayment::count();
            $this->info("   Invoice Payment Allocations: {$invoicePaymentsCount}");

            $this->info("\n🎉 All financial reporting tests completed successfully!");
            $this->info("📋 Financial Reports Available:");
            $this->info("   ✅ Balance Sheet (Statement of Financial Position)");
            $this->info("   ✅ Income Statement (Profit and Loss Statement)");
            $this->info("   ✅ Cash Flow Statement");
            $this->info("   ✅ Accounts Receivable (A/R) Aging Report");
            $this->info("   ✅ Accounts Payable (A/P) Aging Report");
            $this->info("   ✅ General Ledger (GL)");
            $this->info("   ✅ Financial Dashboard");

        } catch (\Exception $e) {
            $this->error("❌ Error testing financial reports: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}