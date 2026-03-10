<?php

namespace App\Livewire\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Location;
use App\Models\Shift;
use App\Models\InventoryStock;
use App\Services\StockAlertService;
use App\Traits\WithToastNotifications;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

#[Layout('components.layouts.app')]
#[Title('Point of Sale')]
class CreateSale extends Component
{
    use WithToastNotifications;

    public $search = '';
    public $cart = [];
    public $payment_method = 'CASH';
    public $notes = '';
    public $location_id;
    public $lastSale = null;
    public $showReceipt = false;
    public $showConfirmModal = false;
    public $activeShift = null;
    public $selectedAttendantId = '';
    public $sale_date = ''; // For admin backdating

    public function mount()
    {
        $user = Auth::user();
        $this->location_id = $user->location_id ?? Location::where('is_active', true)->first()?->id;
        $this->sale_date = now()->format('Y-m-d'); // Default to today
        
        // If user is attendant, check for their active shift
        if ($user->role === 'attendant') {
            $this->activeShift = Shift::where('attendant_id', $user->id)
                ->where('is_active', true)
                ->first();
        }
        
        // Auto-select attendant if cashier/admin and only one on shift
        if (in_array($user->role, ['cashier', 'admin'])) {
            $activeShifts = Shift::where('is_active', true)
                ->when($this->location_id, fn($q) => $q->where('location_id', $this->location_id))
                ->get();
            
            if ($activeShifts->count() === 1) {
                $this->selectedAttendantId = $activeShifts->first()->attendant_id;
            }
        }
    }

    public function addToCart($productId)
    {
        $product = Product::find($productId);
        if (!$product) return;

        $stock = $this->getProductStock($productId);
        $currentQty = $this->cart[$productId]['quantity'] ?? 0;

        if ($currentQty >= $stock) {
            $this->error('Insufficient stock for ' . $product->name);
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity']++;
        } else {
            $this->cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'cost_price' => $product->cost_price,
                'quantity' => 1,
            ];
        }

        $this->dispatch('cart-updated', cart: $this->cart);
    }

    public function updateQuantity($productId, $quantity)
    {
        $quantity = (int) $quantity;
        
        if ($quantity <= 0) {
            unset($this->cart[$productId]);
            return;
        }

        $stock = $this->getProductStock($productId);
        if ($quantity > $stock) {
            $this->error('Insufficient stock. Available: ' . $stock);
            // Reset to max available
            if (isset($this->cart[$productId])) {
                $this->cart[$productId]['quantity'] = $stock;
            }
            return;
        }

        if (isset($this->cart[$productId])) {
            $this->cart[$productId]['quantity'] = $quantity;
        }
    }

    public function validateCartQuantity($productId)
    {
        if (!isset($this->cart[$productId])) {
            return;
        }
        
        $quantity = (int) $this->cart[$productId]['quantity'];
        
        if ($quantity <= 0) {
            unset($this->cart[$productId]);
            return;
        }

        $stock = $this->getProductStock($productId);
        if ($quantity > $stock) {
            $this->error('Insufficient stock. Available: ' . $stock);
            $this->cart[$productId]['quantity'] = $stock;
        }
    }

    public function removeFromCart($productId)
    {
        unset($this->cart[$productId]);
        $this->dispatch('cart-updated', cart: $this->cart);
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->dispatch('cart-updated', cart: $this->cart);
    }

    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function getTotalProperty()
    {
        return $this->subtotal;
    }

    public function completeSale()
    {
        if (empty($this->cart)) {
            $this->showConfirmModal = false;
            $this->error('Cart is empty.');
            return;
        }

        if (!$this->location_id) {
            $this->showConfirmModal = false;
            $this->error('Please select a location.');
            return;
        }

        $user = Auth::user();
        $shiftId = null;
        $attendantId = null;

        // Determine shift and attendant based on user role
        if ($user->role === 'attendant') {
            // Attendant must have an active shift
            $shift = Shift::where('attendant_id', $user->id)
                ->where('is_active', true)
                ->first();
            
            if (!$shift) {
                $this->showConfirmModal = false;
                $this->error('You do not have an active shift. Please ask your cashier to start your shift.');
                return;
            }
            
            $shiftId = $shift->id;
            $attendantId = $user->id;
        } elseif (in_array($user->role, ['cashier', 'admin'])) {
            // Check if there are active attendants on shift
            $activeShiftCount = Shift::where('is_active', true)
                ->when($this->location_id, fn($q) => $q->where('location_id', $this->location_id))
                ->count();
            
            if ($activeShiftCount > 0 && !$this->selectedAttendantId) {
                $this->showConfirmModal = false;
                $this->error('Please select an attendant for this sale.');
                return;
            }
            
            if ($this->selectedAttendantId) {
                $shift = Shift::where('attendant_id', $this->selectedAttendantId)
                    ->where('is_active', true)
                    ->first();
                
                if ($shift) {
                    $shiftId = $shift->id;
                    $attendantId = $this->selectedAttendantId;
                } else {
                    $this->showConfirmModal = false;
                    $this->error('Selected attendant does not have an active shift.');
                    return;
                }
            }
        }

        // Validate sale_date for non-admin users (must be today)
        $isAdmin = $user->role === 'admin';
        $saleDate = $this->sale_date ?: now()->format('Y-m-d');
        
        if (!$isAdmin && $saleDate !== now()->format('Y-m-d')) {
            $this->showConfirmModal = false;
            $this->error('Only admin can record sales for past dates.');
            return;
        }

        DB::beginTransaction();
        try {
            // Create sale
            $sale = Sale::create([
                'location_id' => $this->location_id,
                'user_id' => Auth::id(),
                'shift_id' => $shiftId,
                'attendant_id' => $attendantId,
                'payment_method' => $this->payment_method,
                'subtotal' => $this->subtotal,
                'total' => $this->total,
                'notes' => $this->notes,
                'sale_date' => $saleDate,
            ]);

            // Add items and deduct stock
            foreach ($this->cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'total' => $item['price'] * $item['quantity'],
                    'profit' => ($item['price'] - $item['cost_price']) * $item['quantity'],
                ]);

                // Deduct from inventory
                $stock = InventoryStock::where('product_id', $item['id'])
                    ->where('location_id', $this->location_id)
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();
            
            // Check for low stock products after sale and alert admins
            $productIds = collect($this->cart)->pluck('id')->toArray();
            $this->checkLowStockAfterSale($productIds);
            
            // Load the sale with items for receipt
            $this->lastSale = Sale::with(['items.product', 'user', 'location'])->find($sale->id);
            $this->showConfirmModal = false;
            $this->showReceipt = true;
            
            $this->cart = [];
            $this->notes = '';
            $this->success('Sale completed! Sale #: ' . $sale->sale_number);

        } catch (\Exception $e) {
            DB::rollback();
            $this->showConfirmModal = false;
            $this->error('Error processing sale: ' . $e->getMessage());
        }
    }

    public function closeReceipt()
    {
        $this->showReceipt = false;
        $this->lastSale = null;
    }

    /**
     * Show receipt for a sale created via API
     */
    #[On('showReceiptForSale')]
    public function showReceiptForSale($saleId)
    {
        $this->lastSale = Sale::with(['items.product', 'user', 'location'])->find($saleId);
        if ($this->lastSale) {
            $this->showReceipt = true;
        }
    }

    public function printReceipt()
    {
        $this->dispatch('print-receipt');
    }

    protected function getProductStock($productId)
    {
        return InventoryStock::where('product_id', $productId)
            ->where('location_id', $this->location_id)
            ->first()?->quantity ?? 0;
    }

    /**
     * Check if any products from the sale are now low on stock and alert admins
     */
    protected function checkLowStockAfterSale(array $productIds): void
    {
        $lowStockProducts = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->whereNotNull('low_stock_threshold')
            ->where('low_stock_threshold', '>', 0)
            ->withSum(['inventoryStocks' => fn($q) => $q->where('location_id', $this->location_id)], 'quantity')
            ->get()
            ->filter(function ($product) {
                $currentStock = $product->inventory_stocks_sum_quantity ?? 0;
                return $currentStock <= $product->low_stock_threshold;
            })
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->inventory_stocks_sum_quantity ?? 0,
                'threshold' => $product->low_stock_threshold,
            ])
            ->toArray();

        if (count($lowStockProducts) > 0) {
            $stockAlertService = app(StockAlertService::class);
            $stockAlertService->sendAlertToAdmins($lowStockProducts, $this->location_id);
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        $products = Product::active()
            ->when($this->search, function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('sku', 'like', "%{$this->search}%")
                  ->orWhere('barcode', 'like', "%{$this->search}%");
            })
            ->withSum(['inventoryStocks' => fn($q) => $q->where('location_id', $this->location_id)], 'quantity')
            ->orderBy('name')
            ->get();

        // Get today's sold quantity per product (admin sees all, others see only their own)
        $isAdmin = $user->role === 'admin';
        $todaySoldByProduct = SaleItem::whereHas('sale', function ($q) use ($user, $isAdmin) {
                $q->whereDate('sale_date', today());
                if (!$isAdmin) {
                    $q->where('user_id', $user->id);
                }
            })
            ->selectRaw('product_id, SUM(quantity) as total_sold')
            ->groupBy('product_id')
            ->pluck('total_sold', 'product_id')
            ->toArray();

        $locations = Location::where('is_active', true)->get();

        // Get active attendants on shift (for cashier/admin to select)
        $activeAttendants = [];
        if (in_array($user->role, ['cashier', 'admin'])) {
            $activeAttendants = Shift::where('is_active', true)
                ->when($this->location_id, fn($q) => $q->where('location_id', $this->location_id))
                ->with('attendant')
                ->get()
                ->pluck('attendant')
                ->filter();
        }

        // Calculate today's sales (admin sees all, others see only their own)
        $salesQuery = Sale::whereDate('sale_date', today());
        if (!$isAdmin) {
            $salesQuery->where('user_id', $user->id);
        }
        $todaySalesTotal = (clone $salesQuery)->sum('total');
        $todaySalesCount = (clone $salesQuery)->count();

        // Today's sales by payment method
        $todayCashSales = (clone $salesQuery)->where('payment_method', 'CASH')->sum('total');
        $todayTransferSales = (clone $salesQuery)->where('payment_method', 'TRANSFER')->sum('total');
        $todayCardSales = (clone $salesQuery)->where('payment_method', 'CARD')->sum('total');

        // Calculate today's expenses (admin sees all, others see only their own)
        $expensesQuery = \App\Models\Expense::whereDate('expense_date', today());
        if (!$isAdmin) {
            $expensesQuery->where('user_id', $user->id);
        }
        $todayExpensesTotal = $expensesQuery->sum('amount');

        // Closing balance = Sales - Expenses
        $closingBalance = $todaySalesTotal - $todayExpensesTotal;

        return view('livewire.sales.create-sale', [
            'products' => $products,
            'locations' => $locations,
            'activeAttendants' => $activeAttendants,
            'todaySalesTotal' => $todaySalesTotal,
            'todaySalesCount' => $todaySalesCount,
            'todayCashSales' => $todayCashSales,
            'todayTransferSales' => $todayTransferSales,
            'todayCardSales' => $todayCardSales,
            'todaySoldByProduct' => $todaySoldByProduct,
            'todayExpensesTotal' => $todayExpensesTotal,
            'closingBalance' => $closingBalance,
        ]);
    }
}
