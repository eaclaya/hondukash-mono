<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class FinancialReportsController extends Controller
{
    public function __construct(
        private FinancialReportingService $reportingService
    ) {}

    /**
     * Generate Balance Sheet
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date|before_or_equal:today',
        ]);

        try {
            $report = $this->reportingService->generateBalanceSheet(
                $request->get('as_of_date')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate balance sheet',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Income Statement
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $report = $this->reportingService->generateIncomeStatement(
                $request->get('start_date'),
                $request->get('end_date')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate income statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Cash Flow Statement
     */
    public function cashFlowStatement(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $report = $this->reportingService->generateCashFlowStatement(
                $request->get('start_date'),
                $request->get('end_date')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate cash flow statement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Accounts Receivable Aging Report
     */
    public function arAging(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date|before_or_equal:today',
        ]);

        try {
            $report = $this->reportingService->generateARAgingReport(
                $request->get('as_of_date')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate A/R aging report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Accounts Payable Aging Report
     */
    public function apAging(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date|before_or_equal:today',
        ]);

        try {
            $report = $this->reportingService->generateAPAgingReport(
                $request->get('as_of_date')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate A/P aging report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate General Ledger Report
     */
    public function generalLedger(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|uuid|exists:accounts,id',
        ]);

        try {
            $report = $this->reportingService->generateGeneralLedger(
                $request->get('start_date'),
                $request->get('end_date'),
                $request->get('account_id')
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate general ledger',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate Financial Dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $request->validate([
            'as_of_date' => 'nullable|date|before_or_equal:today',
        ]);

        try {
            $dashboard = $this->reportingService->generateFinancialDashboard(
                $request->get('as_of_date')
            );

            return response()->json([
                'success' => true,
                'data' => $dashboard,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate financial dashboard',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available date ranges for reports
     */
    public function dateRanges(): JsonResponse
    {
        try {
            $currentYear = now()->year;
            $ranges = [
                'current_month' => [
                    'name' => 'Current Month',
                    'start_date' => now()->startOfMonth()->toDateString(),
                    'end_date' => now()->endOfMonth()->toDateString(),
                ],
                'last_month' => [
                    'name' => 'Last Month',
                    'start_date' => now()->subMonth()->startOfMonth()->toDateString(),
                    'end_date' => now()->subMonth()->endOfMonth()->toDateString(),
                ],
                'current_quarter' => [
                    'name' => 'Current Quarter',
                    'start_date' => now()->startOfQuarter()->toDateString(),
                    'end_date' => now()->endOfQuarter()->toDateString(),
                ],
                'last_quarter' => [
                    'name' => 'Last Quarter',
                    'start_date' => now()->subQuarter()->startOfQuarter()->toDateString(),
                    'end_date' => now()->subQuarter()->endOfQuarter()->toDateString(),
                ],
                'current_year' => [
                    'name' => 'Current Year',
                    'start_date' => now()->startOfYear()->toDateString(),
                    'end_date' => now()->endOfYear()->toDateString(),
                ],
                'last_year' => [
                    'name' => 'Last Year',
                    'start_date' => now()->subYear()->startOfYear()->toDateString(),
                    'end_date' => now()->subYear()->endOfYear()->toDateString(),
                ],
                'ytd' => [
                    'name' => 'Year to Date',
                    'start_date' => now()->startOfYear()->toDateString(),
                    'end_date' => now()->toDateString(),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $ranges,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get date ranges',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export report to PDF or Excel (placeholder for future implementation)
     */
    public function export(Request $request): JsonResponse
    {
        $request->validate([
            'report_type' => 'required|in:balance_sheet,income_statement,cash_flow,ar_aging,ap_aging,general_ledger',
            'format' => 'required|in:pdf,excel',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'as_of_date' => 'nullable|date',
            'account_id' => 'nullable|uuid',
        ]);

        // TODO: Implement PDF/Excel export functionality
        return response()->json([
            'success' => false,
            'message' => 'Export functionality not yet implemented',
        ], 501);
    }
}