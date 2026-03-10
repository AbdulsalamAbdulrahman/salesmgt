<div>
    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Purchase Orders</h1>
        
        <div class="flex items-center gap-4">
            <select wire:model.live="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="sent">Sent</option>
                <option value="rejected">Rejected</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
            </select>

            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search orders..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            @if(in_array(auth()->user()->role, ['admin', 'cashier']))
            <button wire:click="createOrder" class="inline-flex items-center px-4 py-2 bg-brand-500 text-white text-sm font-medium rounded-lg hover:bg-brand-600 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Order
            </button>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-mono font-medium text-gray-900">{{ $order->order_number }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->location->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->requester->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ $order->items->count() }} items
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @switch($order->status)
                                    @case('pending') bg-yellow-100 text-yellow-800 @break
                                    @case('approved') bg-brand-100 text-brand-700 @break
                                    @case('rejected') bg-red-100 text-red-800 @break
                                    @case('ordered') bg-brand-100 text-brand-700 @break
                                    @case('delivered') bg-green-100 text-green-800 @break
                                    @case('cancelled') bg-gray-100 text-gray-600 @break
                                @endswitch
                            ">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $order->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <button wire:click="viewOrder({{ $order->id }})" class="text-brand-500 hover:text-brand-700">
                                View
                            </button>
                            
                            @if(auth()->user()->role === 'admin' && $order->status === 'pending')
                                <button wire:click="openApprovalModal({{ $order->id }})" class="text-green-600 hover:text-green-900">
                                    Review
                                </button>
                            @endif
                            
                            @if(auth()->user()->role === 'supplier' && $order->status === 'approved')
                                <button wire:click="openSendModal({{ $order->id }})" class="text-purple-600 hover:text-purple-900">
                                    Mark as Sent
                                </button>
                            @endif
                            
                            @if(in_array(auth()->user()->role, ['admin', 'cashier']) && in_array($order->status, ['approved', 'sent', 'ordered']))
                                <button wire:click="openDeliveryModal({{ $order->id }})" class="text-brand-500 hover:text-brand-700">
                                    Receive
                                </button>
                            @endif

                            @if(auth()->user()->role !== 'supplier' && $order->status === 'pending')
                                <button wire:click="confirmCancel({{ $order->id }})" class="text-red-600 hover:text-red-900">
                                    Cancel
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No purchase orders found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t">
            {{ $orders->links() }}
        </div>
    </div>

    <!-- Send Order Modal (Supplier) - Alpine.js powered -->
    @if($showSendModal && $selectedOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{
            items: @js($orderItems),
            markAsSent() {
                $wire.set('orderItems', this.items);
                $wire.markAsSent();
            }
         }">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" wire:click="$set('showSendModal', false)"></div>
            
            <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                <div class="px-6 py-4 border-b bg-gradient-to-r from-purple-600 to-violet-600 rounded-t-lg">
                    <h3 class="text-lg font-semibold text-white">Send Order: {{ $selectedOrder->order_number }}</h3>
                    <p class="text-purple-100 text-sm">Adjust quantities if you cannot fulfill the full order</p>
                </div>
                
                <div class="p-6">
                    <div class="mb-4 p-3 bg-purple-50 border border-purple-200 rounded-lg text-sm text-purple-700">
                        <strong>Location:</strong> {{ $selectedOrder->location->name ?? 'N/A' }}<br>
                        <strong>Requested By:</strong> {{ $selectedOrder->requester->name ?? 'N/A' }}<br>
                        <strong>Approved By:</strong> {{ $selectedOrder->approver->name ?? 'N/A' }}<br>
                        <strong>Requested:</strong> {{ $selectedOrder->created_at->format('M d, Y') }}
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-700">Product</th>
                                    <th class="px-4 py-3 text-center font-medium text-gray-700">Approved Qty</th>
                                    <th class="px-4 py-3 text-center font-medium text-gray-700">Sending Qty</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-4 py-3 text-gray-800" x-text="item.product_name"></td>
                                    <td class="px-4 py-3 text-center text-gray-600" x-text="item.approved_quantity"></td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" 
                                            x-model.number="item.sending_quantity"
                                            min="0"
                                            :max="item.approved_quantity"
                                            class="w-20 px-2 py-1 border rounded text-center">
                                    </td>
                                </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t flex justify-end gap-3 rounded-b-lg">
                    <button wire:click="$set('showSendModal', false)" type="button" class="px-4 py-2 border rounded-lg hover:bg-gray-100">
                        Cancel
                    </button>
                    <button @click="markAsSent()" type="button" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        Confirm & Send
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Create Order Modal - Alpine.js powered for client-side calculations -->
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{
            items: @js($orderItems),
            products: @js($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'cost_price' => $p->cost_price, 'low_stock_threshold' => $p->low_stock_threshold, 'unit_type' => $p->unit_type ?? 'piece', 'qty_per_unit' => $p->qty_per_unit ?? 1])),
            location_id: @js($location_id ?? ''),
            notes: '',
            formatCurrency(amount) {
                return new Intl.NumberFormat('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
            },
            lineTotal(item) {
                return (parseFloat(item.cost_price) || 0) * (parseInt(item.quantity) || 0);
            },
            grandTotal() {
                return this.items.filter(item => item.selected).reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            allSelected() {
                return this.items.length > 0 && this.items.every(item => item.selected);
            },
            toggleSelectAll() {
                const newState = !this.allSelected();
                this.items.forEach(item => item.selected = newState);
            },
            selectedCount() {
                return this.items.filter(item => item.selected).length;
            },
            removeSelected() {
                if (this.selectedCount() === 0) {
                    alert('No items selected.');
                    return;
                }
                if (confirm('Remove ' + this.selectedCount() + ' selected item(s)?')) {
                    this.items = this.items.filter(item => !item.selected);
                }
            },
            removeItem(index) {
                this.items.splice(index, 1);
            },
            addProduct() {
                this.items.push({
                    product_id: '',
                    product_name: '',
                    current_stock: 0,
                    threshold: 0,
                    cost_price: 0,
                    quantity: 10,
                    unit_type: 'piece',
                    qty_per_unit: 1,
                    selected: true
                });
            },
            isProductAlreadyAdded(productId) {
                return this.items.some(item => item.product_id == productId);
            },
            getAvailableProducts() {
                const addedProductIds = this.items.map(item => item.product_id).filter(id => id);
                return this.products.filter(p => !addedProductIds.includes(p.id));
            },
            selectProduct(index, productId) {
                if (!productId) return;
                
                // Check if product is already added
                if (this.isProductAlreadyAdded(productId)) {
                    alert('This product has already been added to the order.');
                    return;
                }
                
                const product = this.products.find(p => p.id == productId);
                if (product) {
                    this.items[index].product_id = product.id;
                    this.items[index].product_name = product.name;
                    this.items[index].cost_price = product.cost_price || 0;
                    this.items[index].threshold = product.low_stock_threshold || 0;
                    this.items[index].unit_type = product.unit_type || 'piece';
                    this.items[index].qty_per_unit = product.qty_per_unit || 1;
                }
            },
            submitOrder() {
                const selectedItems = this.items.filter(item => item.selected && item.product_id && item.quantity > 0);
                if (selectedItems.length === 0) {
                    alert('Please select at least one product with quantity.');
                    return;
                }
                if (!this.location_id || this.location_id === '' || this.location_id === null) {
                    alert('Please select a location for this order.');
                    return;
                }
                // Update Livewire state and submit - wait for all sets to complete
                Promise.all([
                    $wire.set('orderItems', this.items),
                    $wire.set('location_id', this.location_id),
                    $wire.set('notes', this.notes)
                ]).then(() => {
                    $wire.submitOrder();
                });
            }
         }">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showCreateModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full mx-auto transform transition-all max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Create Purchase Order</h3>
                            <p class="text-sm text-gray-500">Order products that are low on stock</p>
                        </div>
                        <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto flex-grow">
                    <!-- Location Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                        <select x-model="location_id" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                            <option value="">Select a location...</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <template x-if="items.length > 0">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">
                                        <input type="checkbox" 
                                               :checked="allSelected()" 
                                               @click="toggleSelectAll()"
                                               class="h-4 w-4 text-brand-500 focus:ring-brand-500 border-gray-300 rounded"
                                               title="Select/Unselect All">
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Threshold</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Order Qty</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Line Total</th>
                                    <th class="px-4 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(item, index) in items" :key="index">
                                <tr :class="item.current_stock <= item.threshold ? 'bg-red-50' : ''">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" x-model="item.selected" 
                                               class="h-4 w-4 text-brand-500 focus:ring-brand-500 border-gray-300 rounded">
                                    </td>
                                    <td class="px-4 py-3">
                                        <template x-if="item.product_id">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900" x-text="item.product_name"></span>
                                                <template x-if="item.qty_per_unit > 1">
                                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-700"
                                                          x-text="(item.unit_type ? item.unit_type.charAt(0).toUpperCase() + item.unit_type.slice(1) : 'Pack') + ' of ' + item.qty_per_unit"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!item.product_id">
                                            <select @change="selectProduct(index, $event.target.value)"
                                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-brand-500 focus:border-brand-500">
                                                <option value="">Select product...</option>
                                                <template x-for="product in getAvailableProducts()" :key="product.id">
                                                    <option :value="product.id" x-text="product.name + (product.qty_per_unit > 1 ? ' (' + (product.unit_type ? product.unit_type.charAt(0).toUpperCase() + product.unit_type.slice(1) : 'Pack') + ' of ' + product.qty_per_unit + ')' : '')"></option>
                                                </template>
                                            </select>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm" :class="item.current_stock <= item.threshold ? 'text-red-600 font-semibold' : 'text-gray-600'" x-text="item.current_stock"></span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500" x-text="item.threshold"></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">₦<span x-text="formatCurrency(item.cost_price)"></span></td>
                                    <td class="px-4 py-3">
                                        <input type="number" x-model.number="item.quantity" min="1"
                                               class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-brand-500 focus:border-brand-500">
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">₦<span x-text="formatCurrency(lineTotal(item))"></span></td>
                                    <td class="px-4 py-3">
                                        <button @click="removeItem(index)" type="button" class="text-red-600 hover:text-red-900">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-100">
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">
                                        Order Total (Selected Items):
                                    </td>
                                    <td class="px-4 py-3 text-right text-lg font-bold text-brand-600">
                                        ₦<span x-text="formatCurrency(grandTotal())"></span>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    </template>
                    
                    <template x-if="items.length === 0">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p>No low stock products found. Add products manually.</p>
                    </div>
                    </template>

                    <div class="mt-4 flex items-center gap-4">
                        <button @click="addProduct()" type="button" class="inline-flex items-center px-3 py-1 text-sm text-brand-500 hover:text-brand-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Product
                        </button>
                        
                        <button @click="removeSelected()" type="button" 
                                x-show="selectedCount() > 0"
                                class="inline-flex items-center px-3 py-1 text-sm text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete Selected (<span x-text="selectedCount()"></span>)
                        </button>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                        <textarea x-model="notes" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                  placeholder="Any notes for this order..."></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex-shrink-0 flex justify-end space-x-3">
                    <button wire:click="$set('showCreateModal', false)" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="submitOrder()" type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-brand-500 rounded-lg hover:bg-brand-600 transition-colors">
                        Submit for Approval
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- View Order Modal -->
    @if($showViewModal && $selectedOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-auto transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $selectedOrder->order_number }}</h3>
                            <p class="text-sm text-gray-500">{{ $selectedOrder->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <button wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-sm text-gray-500">Location:</span>
                            <p class="font-medium">{{ $selectedOrder->location->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Requested By:</span>
                            <p class="font-medium">{{ $selectedOrder->requester->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Status:</span>
                            <p><span class="px-2 py-1 text-xs font-semibold rounded-full 
                                @switch($selectedOrder->status)
                                    @case('pending') bg-yellow-100 text-yellow-800 @break
                                    @case('approved') bg-brand-100 text-brand-700 @break
                                    @case('sent') bg-purple-100 text-purple-800 @break
                                    @case('rejected') bg-red-100 text-red-800 @break
                                    @case('delivered') bg-green-100 text-green-800 @break
                                @endswitch
                            ">{{ ucfirst($selectedOrder->status) }}</span></p>
                        </div>
                        @if($selectedOrder->approver)
                        <div>
                            <span class="text-sm text-gray-500">Approved By:</span>
                            <p class="font-medium">{{ $selectedOrder->approver->name }}</p>
                        </div>
                        @endif
                        @if($selectedOrder->sender)
                        <div>
                            <span class="text-sm text-gray-500">Supplier:</span>
                            <p class="font-medium">{{ $selectedOrder->sender->name }}</p>
                            <p class="text-xs text-gray-400">Sent: {{ $selectedOrder->sent_at?->format('M d, Y H:i') }}</p>
                        </div>
                        @endif
                    </div>

                    @if($selectedOrder->rejection_reason)
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <span class="text-sm font-medium text-red-800">Rejection Reason:</span>
                        <p class="text-sm text-red-600">{{ $selectedOrder->rejection_reason }}</p>
                    </div>
                    @endif

                    <h4 class="font-medium text-gray-900 mb-3">Order Items</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Requested</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Delivered</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @php $orderTotal = 0; @endphp
                                @foreach($selectedOrder->items as $item)
                                @php 
                                    $qty = (int)($item->delivered_quantity ?? $item->approved_quantity ?? $item->requested_quantity);
                                    $lineTotal = (float)($item->unit_cost ?? 0) * $qty;
                                    $orderTotal += $lineTotal;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->product->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">₦{{ number_format($item->unit_cost ?? 0, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-center">{{ $item->requested_quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-center">{{ $item->approved_quantity ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-center">{{ $item->delivered_quantity ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">₦{{ number_format($lineTotal, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Order Total:</td>
                                    <td class="px-4 py-3 text-sm font-bold text-green-600 text-right">₦{{ number_format($orderTotal, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($selectedOrder->notes)
                    <div class="mt-4">
                        <span class="text-sm font-medium text-gray-700">Notes:</span>
                        <p class="text-sm text-gray-600 mt-1">{{ $selectedOrder->notes }}</p>
                    </div>
                    @endif
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                    <button wire:click="$set('showViewModal', false)" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Approval Modal - Alpine.js powered -->
    @if($showApprovalModal && $selectedOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{
            items: @js($orderItems),
            rejectionReason: '',
            formatCurrency(amount) {
                return new Intl.NumberFormat('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount);
            },
            lineTotal(item) {
                return (parseFloat(item.unit_cost) || 0) * (parseInt(item.approved_quantity) || 0);
            },
            grandTotal() {
                return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
            },
            approveOrder() {
                $wire.set('orderItems', this.items);
                $wire.approveOrder();
            },
            rejectOrder() {
                $wire.set('orderItems', this.items);
                $wire.set('rejectionReason', this.rejectionReason);
                $wire.rejectOrder();
            }
         }">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showApprovalModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-auto transform transition-all max-h-[90vh] overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Review Order: {{ $selectedOrder->order_number }}</h3>
                            <p class="text-sm text-gray-500">Requested by {{ $selectedOrder->requester->name ?? 'N/A' }} on {{ $selectedOrder->created_at->format('M d, Y') }}</p>
                        </div>
                        <button wire:click="$set('showApprovalModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 overflow-y-auto flex-grow">
                    <p class="text-sm text-gray-600 mb-4">Review and adjust quantities before approving.</p>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Requested</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Approved</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Line Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="item.product_name"></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-right">₦<span x-text="formatCurrency(item.unit_cost)"></span></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 text-center" x-text="item.requested_quantity"></td>
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" x-model.number="item.approved_quantity" 
                                               min="0" :max="item.requested_quantity * 2"
                                               class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-brand-500 focus:border-brand-500 text-center">
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">₦<span x-text="formatCurrency(lineTotal(item))"></span></td>
                                </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">Order Total:</td>
                                    <td class="px-4 py-3 text-sm font-bold text-green-600 text-right">
                                        ₦<span x-text="formatCurrency(grandTotal())"></span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rejection Reason (if rejecting)</label>
                        <textarea x-model="rejectionReason" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent"
                                  placeholder="Reason for rejection..."></textarea>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex-shrink-0 flex justify-end space-x-3">
                    <button wire:click="$set('showApprovalModal', false)" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="rejectOrder()" type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                        Reject
                    </button>
                    <button @click="approveOrder()" type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                        Approve
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Delivery Modal - Alpine.js powered -->
    @if($showDeliveryModal && $selectedOrder)
    <div class="fixed inset-0 z-50 overflow-y-auto"
         x-data="{
            items: @js($orderItems),
            confirmDelivery() {
                $wire.set('orderItems', this.items);
                $wire.confirmDelivery();
            }
         }">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="$set('showDeliveryModal', false)"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full mx-auto transform transition-all">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirm Delivery: {{ $selectedOrder->order_number }}</h3>
                            <p class="text-sm text-gray-500">Enter quantities received</p>
                        </div>
                        <button wire:click="$set('showDeliveryModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div class="bg-brand-50 border border-brand-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-brand-700">
                            <strong>Note:</strong> Upon confirmation, stock quantities will be automatically updated.
                        </p>
                    </div>
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Approved Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Delivered Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="item.product_name"></td>
                                <td class="px-4 py-3 text-sm text-gray-600" x-text="item.approved_quantity"></td>
                                <td class="px-4 py-3">
                                    <input type="number" x-model.number="item.delivered_quantity" 
                                           min="0"
                                           class="w-20 px-2 py-1 text-sm border border-gray-300 rounded focus:ring-brand-500 focus:border-brand-500">
                                </td>
                            </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button wire:click="$set('showDeliveryModal', false)" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmDelivery()" type="button"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors">
                        Confirm Delivery & Update Stock
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Cancel Order Confirmation Modal -->
    <div x-data="{ open: @entangle('showCancelModal') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"
                 @click="$wire.set('showCancelModal', false)"></div>

            <!-- Modal Panel -->
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-auto">
                <div class="p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Cancel Order</h3>
                    <p class="text-gray-500 text-center mb-6">Are you sure you want to cancel this purchase order? This action cannot be undone.</p>
                    
                    <div class="flex justify-center space-x-3">
                        <button wire:click="$set('showCancelModal', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            No, Keep Order
                        </button>
                        <button wire:click="cancelOrder" 
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Yes, Cancel Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
