<?php

namespace App\Livewire\PurchaseOrders;

use App\Mail\NewPurchaseOrderRequest;
use App\Mail\OrderSentBySupplier;
use App\Mail\PurchaseOrderApproved;
use App\Mail\StockUpdatedAfterDelivery;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\User;
use App\Traits\WithToastNotifications;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Purchase Orders')]
class PurchaseOrderIndex extends Component
{
    use WithPagination, WithToastNotifications;

    public $search = '';
    public $statusFilter = '';
    public $showCreateModal = false;
    public $showViewModal = false;
    public $showApprovalModal = false;
    public $showDeliveryModal = false;
    public $showSendModal = false;
    public $showCancelModal = false;
    public $cancelOrderId = null;
    
    public $selectedOrder;
    public $orderItems = [];
    public $notes = '';
    public $rejectionReason = '';
    public $location_id = '';

    protected $listeners = ['refreshOrders' => '$refresh'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function createOrder()
    {
        $this->reset(['orderItems', 'notes', 'location_id']);
        
        // Pre-populate with low stock items
        $user = Auth::user();
        
        // Set default location from user or first available
        $this->location_id = $user->location_id ?? '';
        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->withSum(['inventoryStocks' => function($q) use ($user) {
                if ($user->location_id) {
                    $q->where('location_id', $user->location_id);
                }
            }], 'quantity')
            ->get()
            ->filter(function ($product) {
                $stock = $product->inventory_stocks_sum_quantity ?? 0;
                return $stock <= $product->low_stock_threshold;
            });

        foreach ($lowStockProducts as $product) {
            $currentStock = $product->inventory_stocks_sum_quantity ?? 0;
            $suggestedQty = max(($product->low_stock_threshold * 2) - $currentStock, 10);
            
            $this->orderItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'current_stock' => $currentStock,
                'threshold' => $product->low_stock_threshold,
                'cost_price' => $product->cost_price,
                'quantity' => $suggestedQty,
                'unit_type' => $product->unit_type ?? 'piece',
                'qty_per_unit' => $product->qty_per_unit ?? 1,
                'selected' => true,
            ];
        }

        $this->showCreateModal = true;
    }

    public function addProduct()
    {
        $this->orderItems[] = [
            'product_id' => '',
            'product_name' => '',
            'current_stock' => 0,
            'threshold' => 0,
            'cost_price' => 0,
            'quantity' => 10,
            'unit_type' => 'piece',
            'qty_per_unit' => 1,
            'selected' => true,
        ];
    }

    public function removeItem($index)
    {
        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
    }

    public function updateProductInfo($index)
    {
        $productId = $this->orderItems[$index]['product_id'];
        if ($productId) {
            $product = Product::withSum(['inventoryStocks' => function($q) {
                $user = Auth::user();
                if ($user->location_id) {
                    $q->where('location_id', $user->location_id);
                }
            }], 'quantity')->find($productId);
            
            if ($product) {
                $this->orderItems[$index]['product_name'] = $product->name;
                $this->orderItems[$index]['current_stock'] = $product->inventory_stocks_sum_quantity ?? 0;
                $this->orderItems[$index]['threshold'] = $product->low_stock_threshold;
                $this->orderItems[$index]['cost_price'] = $product->cost_price;
                $this->orderItems[$index]['unit_type'] = $product->unit_type ?? 'piece';
                $this->orderItems[$index]['qty_per_unit'] = $product->qty_per_unit ?? 1;
            }
        }
    }

    public function submitOrder()
    {
        $selectedItems = array_filter($this->orderItems, fn($item) => $item['selected'] && $item['product_id'] && $item['quantity'] > 0);
        
        if (empty($selectedItems)) {
            $this->error('Please select at least one product with quantity.');
            return;
        }

        if (empty($this->location_id)) {
            $this->error('Please select a location for this order.');
            return;
        }

        $user = Auth::user();

        $order = PurchaseOrder::create([
            'location_id' => $this->location_id,
            'requested_by' => $user->id,
            'status' => PurchaseOrder::STATUS_PENDING,
            'notes' => $this->notes,
        ]);

        foreach ($selectedItems as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $item['product_id'],
                'requested_quantity' => $item['quantity'],
                'unit_cost' => $item['cost_price'] ?? null,
            ]);
        }

        // Send email notification to all admins
        $order->load(['items.product', 'requester', 'location']);
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new NewPurchaseOrderRequest($order));
        }

        $this->showCreateModal = false;
        $this->reset(['orderItems', 'notes']);
        $this->success('Purchase order submitted successfully for approval.');
    }

    public function viewOrder($orderId)
    {
        $this->selectedOrder = PurchaseOrder::with(['items.product', 'requester', 'approver', 'location'])->find($orderId);
        $this->showViewModal = true;
    }

    public function openApprovalModal($orderId)
    {
        $this->selectedOrder = PurchaseOrder::with(['items.product', 'requester', 'location'])->find($orderId);
        
        // Prepare items for editing
        $this->orderItems = $this->selectedOrder->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'requested_quantity' => $item->requested_quantity,
                'approved_quantity' => $item->requested_quantity,
                'unit_cost' => $item->unit_cost ?? $item->product->cost_price ?? 0,
            ];
        })->toArray();
        
        $this->rejectionReason = '';
        $this->showApprovalModal = true;
    }

    public function approveOrder()
    {
        if (!$this->selectedOrder) return;

        // Update item quantities
        foreach ($this->orderItems as $item) {
            PurchaseOrderItem::where('id', $item['id'])->update([
                'approved_quantity' => $item['approved_quantity'],
            ]);
        }

        $this->selectedOrder->approve(Auth::id());
        
        // Send email notification to all suppliers
        $this->selectedOrder->load(['items.product', 'requester', 'approver', 'location']);
        $suppliers = User::where('role', 'supplier')->where('is_active', true)->get();
        foreach ($suppliers as $supplier) {
            Mail::to($supplier->email)->queue(new PurchaseOrderApproved($this->selectedOrder));
        }
        
        $this->showApprovalModal = false;
        $this->reset(['selectedOrder', 'orderItems']);
        $this->success('Purchase order approved successfully.');
    }

    public function rejectOrder()
    {
        if (!$this->selectedOrder) return;

        $this->selectedOrder->reject(Auth::id(), $this->rejectionReason);
        
        $this->showApprovalModal = false;
        $this->reset(['selectedOrder', 'orderItems', 'rejectionReason']);
        $this->success('Purchase order rejected.');
    }

    public function openDeliveryModal($orderId)
    {
        $this->selectedOrder = PurchaseOrder::with(['items.product', 'requester', 'location'])->find($orderId);
        
        $this->orderItems = $this->selectedOrder->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'approved_quantity' => $item->approved_quantity ?? $item->requested_quantity,
                'delivered_quantity' => $item->delivered_quantity ?? $item->approved_quantity ?? $item->requested_quantity,
            ];
        })->toArray();
        
        $this->showDeliveryModal = true;
    }

    public function confirmDelivery()
    {
        if (!$this->selectedOrder) return;

        // Update delivered quantities
        foreach ($this->orderItems as $item) {
            PurchaseOrderItem::where('id', $item['id'])->update([
                'delivered_quantity' => $item['delivered_quantity'],
            ]);
        }

        // Reload the order to get updated items
        $this->selectedOrder->refresh();
        $this->selectedOrder->markAsDelivered(Auth::id());

        // Notify all cashiers about stock update
        $cashiers = User::where('role', 'cashier')
            ->where('is_active', true)
            ->get();
        
        foreach ($cashiers as $cashier) {
            Mail::to($cashier->email)->queue(new StockUpdatedAfterDelivery($this->selectedOrder));
        }
        
        $this->showDeliveryModal = false;
        $this->reset(['selectedOrder', 'orderItems']);
        $this->success('Order marked as delivered. Stock has been updated.');
    }

    // Supplier: Open send modal to adjust quantities and mark as sent
    public function openSendModal($orderId)
    {
        $this->selectedOrder = PurchaseOrder::with(['items.product', 'requester', 'location'])->find($orderId);
        
        $this->orderItems = $this->selectedOrder->items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'approved_quantity' => $item->approved_quantity ?? $item->requested_quantity,
                'sending_quantity' => $item->approved_quantity ?? $item->requested_quantity,
            ];
        })->toArray();
        
        $this->showSendModal = true;
    }

    // Supplier: Mark order as sent with adjusted quantities
    public function markAsSent()
    {
        if (!$this->selectedOrder) return;

        // Update the delivered_quantity with what supplier is actually sending
        foreach ($this->orderItems as $item) {
            PurchaseOrderItem::where('id', $item['id'])->update([
                'delivered_quantity' => $item['sending_quantity'],
            ]);
        }

        $this->selectedOrder->markAsSent(Auth::id());
        
        // Send email notification to all admins
        $this->selectedOrder->load(['items.product', 'requester', 'sender', 'location']);
        $admins = User::where('role', 'admin')->where('is_active', true)->get();
        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new OrderSentBySupplier($this->selectedOrder));
        }
        
        $this->showSendModal = false;
        $this->reset(['selectedOrder', 'orderItems']);
        $this->success('Order marked as sent.');
    }

    public function confirmCancel($orderId)
    {
        $this->cancelOrderId = $orderId;
        $this->showCancelModal = true;
    }

    public function cancelOrder()
    {
        $order = PurchaseOrder::find($this->cancelOrderId);
        if ($order && $order->status === PurchaseOrder::STATUS_PENDING) {
            $order->update(['status' => PurchaseOrder::STATUS_CANCELLED]);
            $this->success('Order cancelled.');
        }
        $this->showCancelModal = false;
        $this->cancelOrderId = null;
    }

    public function render()
    {
        $user = Auth::user();
        
        $orders = PurchaseOrder::query()
            ->with(['items', 'requester', 'location', 'sender'])
            ->when($user->role === 'supplier', fn($q) => $q->whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_DELIVERED]))
            ->when($user->role !== 'admin' && $user->role !== 'supplier', fn($q) => $q->where('location_id', $user->location_id))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where('order_number', 'like', "%{$this->search}%")
                  ->orWhereHas('requester', fn($q2) => $q2->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        $products = Product::where('is_active', true)->orderBy('name')->get();
        $locations = \App\Models\Location::where('is_active', true)->orderBy('name')->get();

        return view('livewire.purchase-orders.purchase-order-index', [
            'orders' => $orders,
            'products' => $products,
            'locations' => $locations,
        ]);
    }
}
