<?php

namespace App\Livewire\Reports;

use App\Models\Sale;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\Location;
use App\Models\Expense;
use App\Models\User;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Reports')]
class ReportIndex extends Component
{
    public $reportType = 'sales';
    public $startDate;
    public $endDate;
    public $locationId = '';

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        
        // Check for query string parameter
        if (request()->query('type') === 'stock') {
            $this->reportType = 'stock';
        }
    }

    public function applyFilters($filters)
    {
        $this->reportType = $filters['reportType'];
        $this->startDate = $filters['startDate'];
        $this->endDate = $filters['endDate'];
        $this->locationId = $filters['locationId'];
    }

    public function exportReport()
    {
        $data = match($this->reportType) {
            'sales' => $this->getSalesReport(),
            'products' => $this->getProductsReport(),
            'profit' => $this->getProfitReport(),
            'staff' => $this->getStaffSummaryReport(),
            'stock' => $this->getStockValuationReport(),
            default => collect(),
        };

        if ($data->isEmpty()) {
            session()->flash('message', 'No data to export for the selected filters.');
            return;
        }

        $filename = $this->reportType . '_report_' . $this->startDate . '_to_' . $this->endDate . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($this->reportType === 'sales') {
                // Sales Report Headers
                fputcsv($handle, ['Sale #', 'Date', 'Cashier', 'Attendant', 'Location', 'Payment Method', 'Items Count', 'Total (₦)']);
                
                foreach ($data as $sale) {
                    fputcsv($handle, [
                        $sale->sale_number,
                        $sale->created_at->format('Y-m-d H:i'),
                        $sale->user->name ?? 'N/A',
                        $sale->attendant->name ?? '-',
                        $sale->location->name ?? 'N/A',
                        $sale->payment_method,
                        $sale->items->count(),
                        number_format($sale->total, 2, '.', ''),
                    ]);
                }

                // Summary row
                fputcsv($handle, []);
                fputcsv($handle, ['SUMMARY']);
                fputcsv($handle, ['Total Sales', $data->count()]);
                fputcsv($handle, ['Total Revenue', number_format($data->sum('total'), 2, '.', '')]);
                fputcsv($handle, ['Cash Sales', number_format($data->where('payment_method', 'CASH')->sum('total'), 2, '.', '')]);
                fputcsv($handle, ['Card Sales', number_format($data->where('payment_method', 'CARD')->sum('total'), 2, '.', '')]);
                fputcsv($handle, ['Transfer Sales', number_format($data->where('payment_method', 'TRANSFER')->sum('total'), 2, '.', '')]);

            } elseif ($this->reportType === 'products') {
                // Products Report Headers
                fputcsv($handle, ['Rank', 'Product Name', 'SKU', 'Quantity Sold', 'Total Revenue (₦)']);
                
                foreach ($data as $index => $item) {
                    fputcsv($handle, [
                        $index + 1,
                        $item->product->name ?? 'Unknown',
                        $item->product->sku ?? 'N/A',
                        $item->total_quantity,
                        number_format($item->total_revenue, 2, '.', ''),
                    ]);
                }

                // Summary row
                fputcsv($handle, []);
                fputcsv($handle, ['SUMMARY']);
                fputcsv($handle, ['Total Products', $data->count()]);
                fputcsv($handle, ['Total Quantity Sold', $data->sum('total_quantity')]);
                fputcsv($handle, ['Total Revenue', number_format($data->sum('total_revenue'), 2, '.', '')]);

            } elseif ($this->reportType === 'profit') {
                // Profit Report Headers
                fputcsv($handle, ['Sale #', 'Date', 'Revenue (₦)', 'Cost (₦)', 'Profit (₦)', 'Margin (%)']);
                
                $totalRevenue = 0;
                $totalCost = 0;
                $totalProfit = 0;

                foreach ($data as $sale) {
                    $revenue = $sale->total;
                    $cost = $sale->items->sum(fn($item) => $item->cost_price * $item->quantity);
                    $profit = $revenue - $cost;
                    $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                    $totalRevenue += $revenue;
                    $totalCost += $cost;
                    $totalProfit += $profit;

                    fputcsv($handle, [
                        $sale->sale_number,
                        $sale->created_at->format('Y-m-d'),
                        number_format($revenue, 2, '.', ''),
                        number_format($cost, 2, '.', ''),
                        number_format($profit, 2, '.', ''),
                        number_format($margin, 2, '.', ''),
                    ]);
                }

                // Summary row
                $overallMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
                fputcsv($handle, []);
                fputcsv($handle, ['TOTALS', '', number_format($totalRevenue, 2, '.', ''), number_format($totalCost, 2, '.', ''), number_format($totalProfit, 2, '.', ''), number_format($overallMargin, 2, '.', '')]);
            
            } elseif ($this->reportType === 'staff') {
                // Staff Summary Report Headers
                fputcsv($handle, ['Staff Name', 'Role', 'Location', 'Sales Count', 'Cash Sales (₦)', 'Transfer Sales (₦)', 'Card Sales (₦)', 'Total Sales (₦)', 'Total Expenses (₦)', 'Net Balance (₦)']);
                
                $grandTotalSales = 0;
                $grandTotalExpenses = 0;
                $grandCashSales = 0;
                $grandTransferSales = 0;
                $grandCardSales = 0;

                foreach ($data as $staff) {
                    $grandTotalSales += $staff->total_sales;
                    $grandTotalExpenses += $staff->total_expenses;
                    $grandCashSales += $staff->cash_sales;
                    $grandTransferSales += $staff->transfer_sales;
                    $grandCardSales += $staff->card_sales;

                    fputcsv($handle, [
                        $staff->name,
                        ucfirst($staff->role),
                        $staff->location->name ?? 'N/A',
                        $staff->sales_count,
                        number_format($staff->cash_sales, 2, '.', ''),
                        number_format($staff->transfer_sales, 2, '.', ''),
                        number_format($staff->card_sales, 2, '.', ''),
                        number_format($staff->total_sales, 2, '.', ''),
                        number_format($staff->total_expenses, 2, '.', ''),
                        number_format($staff->total_sales - $staff->total_expenses, 2, '.', ''),
                    ]);
                }

                // Summary row
                fputcsv($handle, []);
                fputcsv($handle, ['TOTALS', '', '', $data->sum('sales_count'), number_format($grandCashSales, 2, '.', ''), number_format($grandTransferSales, 2, '.', ''), number_format($grandCardSales, 2, '.', ''), number_format($grandTotalSales, 2, '.', ''), number_format($grandTotalExpenses, 2, '.', ''), number_format($grandTotalSales - $grandTotalExpenses, 2, '.', '')]);
            
            } elseif ($this->reportType === 'stock') {
                // Stock Valuation Report Headers
                fputcsv($handle, ['Product', 'SKU', 'Category', 'Location', 'Unit Type', 'Qty/Unit', 'Stock Qty', 'Cost Price', 'Sell Price', 'Value at Cost', 'Value at Selling', 'Expected Profit']);
                
                $totalCostValue = 0;
                $totalSellingValue = 0;
                $totalProfit = 0;

                foreach ($data as $item) {
                    $totalCostValue += $item->cost_value;
                    $totalSellingValue += $item->selling_value;
                    $totalProfit += $item->expected_profit;

                    fputcsv($handle, [
                        $item->name,
                        $item->sku ?? 'N/A',
                        $item->category_name ?? 'Uncategorized',
                        $item->location_name ?? 'N/A',
                        ucfirst($item->unit_type ?? 'piece'),
                        $item->qty_per_unit ?? 1,
                        $item->quantity,
                        number_format($item->cost_price, 2, '.', ''),
                        number_format($item->selling_price, 2, '.', ''),
                        number_format($item->cost_value, 2, '.', ''),
                        number_format($item->selling_value, 2, '.', ''),
                        number_format($item->expected_profit, 2, '.', ''),
                    ]);
                }

                // Summary row
                fputcsv($handle, []);
                fputcsv($handle, ['TOTALS', '', '', '', '', '', $data->sum('quantity'), '', '', number_format($totalCostValue, 2, '.', ''), number_format($totalSellingValue, 2, '.', ''), number_format($totalProfit, 2, '.', '')]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function render()
    {
        $data = [];

        if ($this->reportType === 'sales') {
            $data = $this->getSalesReport();
        } elseif ($this->reportType === 'products') {
            $data = $this->getProductsReport();
        } elseif ($this->reportType === 'profit') {
            $data = $this->getProfitReport();
        } elseif ($this->reportType === 'staff') {
            $data = $this->getStaffSummaryReport();
        } elseif ($this->reportType === 'stock') {
            $data = $this->getStockValuationReport();
        }

        $locations = Location::where('is_active', true)->get();

        // Calculate expenses and closing balance for sales report
        $totalExpenses = 0;
        $closingBalance = 0;
        if ($this->reportType === 'sales') {
            $totalExpenses = Expense::query()
                ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
                ->whereBetween('expense_date', [$this->startDate, $this->endDate])
                ->sum('amount');
            $totalRevenue = collect($data)->sum('total');
            $closingBalance = $totalRevenue - $totalExpenses;
        }

        // Stock valuation summary
        $stockSummary = null;
        if ($this->reportType === 'stock') {
            $stockSummary = InventoryStock::join('products', 'inventory_stocks.product_id', '=', 'products.id')
                ->when($this->locationId, fn($q) => $q->where('inventory_stocks.location_id', $this->locationId))
                ->selectRaw('SUM(inventory_stocks.quantity * (products.cost_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1))) as cost_value')
                ->selectRaw('SUM(inventory_stocks.quantity * (products.selling_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1))) as selling_value')
                ->selectRaw('SUM(inventory_stocks.quantity) as total_quantity')
                ->first();
        }

        return view('livewire.reports.report-index', [
            'reportData' => $data,
            'locations' => $locations,
            'totalExpenses' => $totalExpenses,
            'closingBalance' => $closingBalance,
            'stockSummary' => $stockSummary,
        ]);
    }

    protected function getSalesReport()
    {
        return Sale::query()
            ->with(['user', 'location', 'items'])
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->whereBetween('sale_date', [$this->startDate, $this->endDate])
            ->latest()
            ->get();
    }

    protected function getProductsReport()
    {
        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->when($this->locationId, fn($q) => $q->where('sales.location_id', $this->locationId))
            ->whereBetween('sales.sale_date', [$this->startDate, $this->endDate])
            ->select('products.id as product_id', 'products.name', 'products.sku')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity, SUM(sale_items.total) as total_revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit(20)
            ->get()
            ->map(function ($item) {
                $item->product = (object) ['name' => $item->name, 'sku' => $item->sku];
                return $item;
            });
    }

    protected function getProfitReport()
    {
        return Sale::query()
            ->with('items')
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->whereBetween('sale_date', [$this->startDate, $this->endDate])
            ->latest()
            ->get();
    }

    protected function getStaffSummaryReport()
    {
        $users = User::query()
            ->with('location')
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->whereIn('role', ['admin', 'cashier'])
            ->where('is_active', true)
            ->get();

        return $users->map(function ($user) {
            // Get sales totals for this user
            $salesQuery = Sale::where('user_id', $user->id)
                ->whereBetween('sale_date', [$this->startDate, $this->endDate]);

            $user->sales_count = $salesQuery->count();
            $user->total_sales = (clone $salesQuery)->sum('total');

            // Sales by payment method
            $user->cash_sales = (clone $salesQuery)->where('payment_method', 'CASH')->sum('total');
            $user->cash_count = (clone $salesQuery)->where('payment_method', 'CASH')->count();
            $user->transfer_sales = (clone $salesQuery)->where('payment_method', 'TRANSFER')->sum('total');
            $user->transfer_count = (clone $salesQuery)->where('payment_method', 'TRANSFER')->count();
            $user->card_sales = (clone $salesQuery)->where('payment_method', 'CARD')->sum('total');
            $user->card_count = (clone $salesQuery)->where('payment_method', 'CARD')->count();

            // Get expenses totals for this user
            $user->total_expenses = Expense::where('user_id', $user->id)
                ->whereBetween('expense_date', [$this->startDate, $this->endDate])
                ->sum('amount');

            return $user;
        })->sortByDesc('total_sales')->values();
    }

    protected function getStockValuationReport()
    {
        return InventoryStock::query()
            ->join('products', 'inventory_stocks.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('locations', 'inventory_stocks.location_id', '=', 'locations.id')
            ->when($this->locationId, fn($q) => $q->where('inventory_stocks.location_id', $this->locationId))
            ->where('inventory_stocks.quantity', '>', 0)
            ->select([
                'products.id',
                'products.name',
                'products.sku',
                'products.cost_price',
                'products.selling_price',
                'products.unit_type',
                'products.qty_per_unit',
                'categories.name as category_name',
                'locations.name as location_name',
                'inventory_stocks.quantity',
            ])
            ->selectRaw('inventory_stocks.quantity * (products.cost_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1)) as cost_value')
            ->selectRaw('inventory_stocks.quantity * (products.selling_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1)) as selling_value')
            ->selectRaw('inventory_stocks.quantity * ((products.selling_price - products.cost_price) / COALESCE(NULLIF(products.qty_per_unit, 0), 1)) as expected_profit')
            ->orderByDesc('inventory_stocks.quantity')
            ->get();
    }
}
