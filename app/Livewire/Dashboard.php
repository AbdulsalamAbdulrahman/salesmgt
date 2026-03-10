<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Expense;
use App\Models\InventoryStock;
use Illuminate\Support\Facades\Auth;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $isAdmin = $user->role === 'admin';
        $locationId = $user->location_id;

        // Today's sales
        $todaySalesQuery = Sale::when(!$isAdmin, function ($query) {
                return $query->where('user_id', Auth::id());
            })
            ->whereDate('sale_date', today());

        $todaySales = (clone $todaySalesQuery)->sum('total');

        $todaySalesCount = (clone $todaySalesQuery)->count();

        // Today's sales by payment method
        $todayCashSales = (clone $todaySalesQuery)->where('payment_method', 'CASH')->sum('total');
        $todayTransferSales = (clone $todaySalesQuery)->where('payment_method', 'TRANSFER')->sum('total');
        $todayCardSales = (clone $todaySalesQuery)->where('payment_method', 'CARD')->sum('total');
        $todayCashCount = (clone $todaySalesQuery)->where('payment_method', 'CASH')->count();
        $todayTransferCount = (clone $todaySalesQuery)->where('payment_method', 'TRANSFER')->count();
        $todayCardCount = (clone $todaySalesQuery)->where('payment_method', 'CARD')->count();

        // This month's sales
        $monthSales = Sale::when(!$isAdmin, function ($query) {
                return $query->where('user_id', Auth::id());
            })
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('total');

        // Today's expenses
        $todayExpenses = Expense::when(!$isAdmin, function ($query) use ($locationId) {
                return $query->where('location_id', $locationId);
            })
            ->whereDate('expense_date', today())
            ->sum('amount');

        // Closing balance (Today's Sales - Today's Expenses)
        $closingBalance = $todaySales - $todayExpenses;

        // Low stock products
        $lowStockProducts = Product::active()
            ->whereHas('inventoryStocks', function ($q) use ($locationId, $isAdmin) {
                if (!$isAdmin) {
                    $q->where('location_id', $locationId);
                }
            })
            ->get()
            ->filter(function ($product) use ($locationId, $isAdmin) {
                $stock = $isAdmin ? $product->total_stock : $product->getStockAtLocation($locationId);
                return $stock <= $product->low_stock_threshold;
            })
            ->count();

        // Total products
        $totalProducts = Product::active()->count();

        // Stock valuation (Total value of remaining stock)
        $stockValueAtCost = 0;
        $stockValueAtSelling = 0;
        $stockExpectedProfit = 0;
        if ($isAdmin) {
            $stockData = InventoryStock::join('products', 'inventory_stocks.product_id', '=', 'products.id')
                ->selectRaw('SUM(inventory_stocks.quantity * (products.cost_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1))) as cost_value')
                ->selectRaw('SUM(inventory_stocks.quantity * (products.selling_price / COALESCE(NULLIF(products.qty_per_unit, 0), 1))) as selling_value')
                ->first();
            
            $stockValueAtCost = $stockData->cost_value ?? 0;
            $stockValueAtSelling = $stockData->selling_value ?? 0;
            $stockExpectedProfit = $stockValueAtSelling - $stockValueAtCost;
        }

        // Today's profit (Admin only): Revenue - Cost - Expenses
        $todayProfit = 0;
        $monthProfit = 0;
        if ($isAdmin) {
            // Get today's cost of goods sold
            $todayCost = Sale::whereDate('sale_date', today())
                ->with('items')
                ->get()
                ->flatMap(fn($sale) => $sale->items)
                ->sum(fn($item) => $item->cost_price * $item->quantity);
            
            $todayProfit = $todaySales - $todayCost - $todayExpenses;

            // Get this month's data for profit
            $monthCost = Sale::whereMonth('sale_date', now()->month)
                ->whereYear('sale_date', now()->year)
                ->with('items')
                ->get()
                ->flatMap(fn($sale) => $sale->items)
                ->sum(fn($item) => $item->cost_price * $item->quantity);

            $monthExpenses = Expense::whereMonth('expense_date', now()->month)
                ->whereYear('expense_date', now()->year)
                ->sum('amount');

            $monthProfit = $monthSales - $monthCost - $monthExpenses;
        }

        // Recent sales
        $recentSales = Sale::with(['user', 'location', 'items.product'])
            ->when(!$isAdmin, function ($query) {
                return $query->where('user_id', Auth::id());
            })
            ->latest('created_at')
            ->take(10)
            ->get();

        return view('livewire.dashboard', [
            'todaySales' => $todaySales,
            'todaySalesCount' => $todaySalesCount,
            'todayCashSales' => $todayCashSales,
            'todayTransferSales' => $todayTransferSales,
            'todayCardSales' => $todayCardSales,
            'todayCashCount' => $todayCashCount,
            'todayTransferCount' => $todayTransferCount,
            'todayCardCount' => $todayCardCount,
            'monthSales' => $monthSales,
            'todayExpenses' => $todayExpenses,
            'closingBalance' => $closingBalance,
            'lowStockProducts' => $lowStockProducts,
            'totalProducts' => $totalProducts,
            'recentSales' => $recentSales,
            'isAdmin' => $isAdmin,
            'stockValueAtCost' => $stockValueAtCost,
            'stockValueAtSelling' => $stockValueAtSelling,
            'stockExpectedProfit' => $stockExpectedProfit,
            'todayProfit' => $todayProfit,
            'monthProfit' => $monthProfit,
        ]);
    }
}
