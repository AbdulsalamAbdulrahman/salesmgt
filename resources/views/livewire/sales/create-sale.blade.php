<div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-8rem)]"
    x-data="posCart()"
    x-init="init()">
    
    <script>
        function posCart() {
            return {
                cart: @js($cart ?? []),
                products: @js($products->keyBy('id')->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => ($p->qty_per_unit ?? 1) > 1 ? $p->selling_price / $p->qty_per_unit : $p->selling_price,
                    'cost_price' => ($p->qty_per_unit ?? 1) > 1 ? $p->cost_price / $p->qty_per_unit : $p->cost_price,
                    'stock' => $p->inventory_stocks_sum_quantity ?? 0
                ])),
                paymentMethod: 'CASH',
                locationId: @js($location_id),
                selectedAttendantId: @js($selectedAttendantId ?? ''),
                isSubmitting: false,
                showConfirmModal: false,
                saleDate: @js($sale_date),
                isAdmin: @js(auth()->user()->role === 'admin'),
                
                init() {
                    // Ensure cart is an object
                    if (Array.isArray(this.cart) && this.cart.length === 0) {
                        this.cart = {};
                    }
                    
                    // Listen for attendant selection changes from Livewire
                    this.$watch('selectedAttendantId', () => {});
                },
                
                formatCurrency(amount) {
                    return new Intl.NumberFormat('en-NG', { 
                        style: 'currency', 
                        currency: 'NGN', 
                        minimumFractionDigits: 0 
                    }).format(amount || 0);
                },
                
                lineTotal(item) {
                    if (!item) return 0;
                    return (parseFloat(item.price) || 0) * (parseInt(item.quantity) || 0);
                },
                
                grandTotal() {
                    let total = 0;
                    Object.values(this.cart).forEach(item => {
                        total += this.lineTotal(item);
                    });
                    return total;
                },
                
                itemCount() {
                    return Object.keys(this.cart).length;
                },
                
                getCartItemsArray() {
                    const items = [];
                    for (const [key, item] of Object.entries(this.cart)) {
                        items.push({
                            key: key,
                            id: item.id,
                            name: item.name,
                            price: item.price,
                            quantity: item.quantity,
                            cost_price: item.cost_price
                        });
                    }
                    return items;
                },
                
                showToast(type, message) {
                    window.dispatchEvent(new CustomEvent('notify', { 
                        detail: { type, message } 
                    }));
                },
                
                addItem(productId) {
                    const key = String(productId);
                    const product = this.products[key] || this.products[productId];
                    
                    if (!product || product.stock <= 0) {
                        this.showToast('warning', 'This product is out of stock');
                        return;
                    }
                    
                    const currentQty = this.cart[key]?.quantity || 0;
                    if (currentQty >= product.stock) {
                        this.showToast('warning', `Only ${product.stock} available in stock`);
                        return;
                    }
                    
                    const newCart = JSON.parse(JSON.stringify(this.cart));
                    
                    if (newCart[key]) {
                        newCart[key].quantity = newCart[key].quantity + 1;
                    } else {
                        newCart[key] = {
                            id: product.id,
                            name: product.name,
                            price: product.price,
                            cost_price: product.cost_price,
                            quantity: 1
                        };
                    }
                    
                    this.cart = newCart;
                    // No Livewire call - pure Alpine
                },
                
                updateQty(key, newQty) {
                    newQty = parseInt(newQty) || 1;
                    if (newQty < 1) newQty = 1;
                    
                    if (this.cart[key]) {
                        // Check stock limit
                        const product = this.products[key] || this.products[this.cart[key].id];
                        const maxStock = product ? product.stock : newQty;
                        if (newQty > maxStock) {
                            this.showToast('warning', `Only ${maxStock} available in stock`);
                            newQty = maxStock;
                        }
                        
                        const newCart = JSON.parse(JSON.stringify(this.cart));
                        newCart[key].quantity = newQty;
                        this.cart = newCart;
                    }
                },
                
                getStock(key) {
                    const product = this.products[key];
                    return product ? product.stock : 0;
                },
                
                increment(key) {
                    if (this.cart[key]) {
                        // Check stock limit
                        const product = this.products[key] || this.products[this.cart[key].id];
                        const maxStock = product ? product.stock : this.cart[key].quantity;
                        if (this.cart[key].quantity >= maxStock) {
                            this.showToast('warning', `Only ${maxStock} available in stock`);
                            return;
                        }
                        
                        const newCart = JSON.parse(JSON.stringify(this.cart));
                        newCart[key].quantity = newCart[key].quantity + 1;
                        this.cart = newCart;
                    }
                },
                
                decrement(key) {
                    if (this.cart[key] && this.cart[key].quantity > 1) {
                        const newCart = JSON.parse(JSON.stringify(this.cart));
                        newCart[key].quantity = newCart[key].quantity - 1;
                        this.cart = newCart;
                    } else if (this.cart[key] && this.cart[key].quantity <= 1) {
                        this.removeItem(key);
                    }
                },
                
                removeItem(key) {
                    const newCart = JSON.parse(JSON.stringify(this.cart));
                    delete newCart[key];
                    this.cart = newCart;
                    // No Livewire call - pure Alpine
                },
                
                clearAllItems() {
                    this.cart = {};
                    // No Livewire call - pure Alpine
                },
                
                // Show confirmation modal
                completeSale() {
                    if (Object.keys(this.cart).length === 0) {
                        this.showToast('error', 'Cart is empty');
                        return;
                    }
                    this.showConfirmModal = true;
                },
                
                closeConfirmModal() {
                    this.showConfirmModal = false;
                },
                
                // Actually submit the sale via API
                async confirmAndSubmit() {
                    if (this.isSubmitting) return;
                    this.isSubmitting = true;
                    
                    // Convert Alpine proxy to plain array for serialization
                    const cartArray = JSON.parse(JSON.stringify(Object.values(this.cart)));
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    
                    // Create plain object payload (no proxies)
                    const payload = {
                        cart: cartArray,
                        payment_method: this.paymentMethod,
                        location_id: this.locationId,
                        selected_attendant_id: this.selectedAttendantId || null,
                        notes: '',
                        offline_id: window.offlineQueue ? window.offlineQueue.generateOfflineId() : null,
                        sale_date: this.saleDate,
                    };
                    
                    try {
                        const response = await fetch('/api/sales', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });
                        
                        const result = await response.json();
                        
                        if (response.ok && result.success) {
                            this.showConfirmModal = false;
                            this.cart = {};
                            this.showToast('success', `Sale completed! #${result.sale_number}`);
                            
                            // Dispatch event for Livewire to show receipt modal
                            Livewire.dispatch('showReceiptForSale', { saleId: result.sale_id });
                        } else {
                            this.showToast('error', result.message || 'Failed to complete sale');
                        }
                    } catch (error) {
                        // Network error - queue for offline sync
                        if (!navigator.onLine && window.offlineQueue) {
                            await window.offlineQueue.addTransaction('sale', payload);
                            this.showConfirmModal = false;
                            this.cart = {};
                            this.showToast('warning', 'Sale saved offline. Will sync when online.');
                        } else {
                            this.showToast('error', 'Network error: ' + error.message);
                        }
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
    
    <!-- Products Section -->
    <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header with Daily Sales Summary -->
        <div class="p-4 border-b border-gray-100">
            <div class="flex items-center justify-between gap-4 mb-3">
                <h2 class="text-xl font-bold text-gray-800">Products</h2>
                
                <!-- Daily Summary -->
                <div class="flex items-center gap-2">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-4 py-2 rounded-xl border border-green-200 min-w-[130px]">
                        <div class="text-xs text-green-600 font-medium">Today's Sales</div>
                        <div class="text-lg font-bold text-green-700">₦{{ number_format($todaySalesTotal, 0) }}</div>
                        <div class="text-xs text-green-500">{{ $todaySalesCount }} {{ Str::plural('txn', $todaySalesCount) }}</div>
                        <div class="mt-1 pt-1 border-t border-green-200 space-y-0.5">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-green-500">Cash</span>
                                <span class="text-green-700 font-semibold">₦{{ number_format($todayCashSales, 0) }}</span>
                            </div>
                            <div class="flex justify-between text-[10px]">
                                <span class="text-green-500">Transfer</span>
                                <span class="text-green-700 font-semibold">₦{{ number_format($todayTransferSales, 0) }}</span>
                            </div>
                            @if($todayCardSales > 0)
                            <div class="flex justify-between text-[10px]">
                                <span class="text-green-500">Card</span>
                                <span class="text-green-700 font-semibold">₦{{ number_format($todayCardSales, 0) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-red-50 to-orange-50 px-4 py-2 rounded-xl border border-red-200 min-w-[130px]">
                        <div class="text-xs text-red-600 font-medium">Today's Expenses</div>
                        <div class="text-lg font-bold text-red-700">₦{{ number_format($todayExpensesTotal, 0) }}</div>
                        <div class="text-xs text-red-500">&nbsp;</div>
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-2 rounded-xl border border-blue-200 min-w-[130px]">
                        <div class="text-xs text-blue-600 font-medium">Closing Balance</div>
                        <div class="text-lg font-bold text-blue-700">₦{{ number_format($closingBalance, 0) }}</div>
                        <div class="text-xs text-blue-500">&nbsp;</div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative flex-1 max-w-md">
                    <input type="text" wire:model.live.debounce.300ms="search" 
                        placeholder="Search by name, SKU or barcode..."
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                
                @if(in_array(auth()->user()->role, ['cashier', 'admin']) && count($activeAttendants) > 0)
                <div class="relative">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Recording for Attendant <span class="text-red-500">*</span></label>
                    <select wire:model="selectedAttendantId" class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50 {{ !$selectedAttendantId ? 'border-amber-300 bg-amber-50' : 'border-green-300 bg-green-50' }}">
                        <option value="">-- Select Attendant --</option>
                        @foreach($activeAttendants as $attendant)
                            <option value="{{ $attendant->id }}">{{ $attendant->name }}</option>
                        @endforeach
                    </select>
                </div>
                @elseif(in_array(auth()->user()->role, ['cashier', 'admin']) && count($activeAttendants) === 0)
                <div class="px-4 py-2 bg-gray-100 text-gray-500 rounded-xl text-sm">
                    <span class="font-medium">No attendants on shift</span>
                </div>
                @endif
            </div>
        </div>

        @if(auth()->user()->role === 'attendant' && !$activeShift)
            <div class="mx-4 mt-4 p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold">No Active Shift</p>
                        <p class="text-sm">Please ask your Cashier to start your shift before making sales.</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mx-4 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
        @endif

        @if(session('success'))
            <div class="mx-4 mt-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-xl text-sm flex items-center">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
        @endif

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-4">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                @forelse($products as $product)
                    @php
                        $stockQty = $product->inventory_stocks_sum_quantity ?? 0;
                        $isOutOfStock = $stockQty <= 0;
                        $soldToday = $todaySoldByProduct[$product->id] ?? 0;
                    @endphp
                    <button @click="addItem({{ $product->id }})" 
                        @if($isOutOfStock) disabled @endif
                        class="group relative bg-gradient-to-br from-gray-50 to-white rounded-xl p-3 border-2 border-transparent hover:border-brand-400 hover:shadow-lg transition-all duration-200 text-left {{ $isOutOfStock ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                        
                        <!-- Stock Badge -->
                        <div class="absolute top-2 right-2">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $isOutOfStock ? 'bg-red-100 text-red-700' : ($stockQty <= 10 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">
                                {{ $stockQty }}
                            </span>
                        </div>
                        
                        <!-- Sold Today Badge -->
                        @if($soldToday > 0)
                        <div class="absolute top-2 left-2">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-brand-100 text-brand-600" title="Sold today">
                                ↑{{ $soldToday }}
                            </span>
                        </div>
                        @endif

                        <!-- Product Image -->
                        <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                            @if($product->image)
                                <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                            @else
                                <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <h3 class="font-medium text-gray-800 text-sm truncate">{{ $product->name }}</h3>
                        <p class="text-lg font-bold text-brand-500">₦{{ number_format(($product->qty_per_unit ?? 1) > 1 ? $product->selling_price / $product->qty_per_unit : $product->selling_price, 0) }}</p>
                    </button>
                @empty
                    <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <p class="text-lg font-medium">No products found</p>
                        <p class="text-sm">Try adjusting your search</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Cart Section -->
    <div class="w-full lg:w-96 flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Cart Header -->
        <div class="p-4 border-b border-gray-100 bg-gradient-to-r from-brand-500 to-brand-600">
            <div class="flex items-center justify-between text-white">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <h2 class="text-lg font-bold">Cart</h2>
                </div>
                <span x-show="itemCount() > 0" class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">
                    <span x-text="itemCount()"></span> <span x-text="itemCount() === 1 ? 'item' : 'items'"></span>
                </span>
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3">
            <div x-show="itemCount() === 0" class="flex flex-col items-center justify-center py-12 text-gray-400">
                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <p class="font-medium">Cart is empty</p>
                <p class="text-sm">Click on products to add</p>
            </div>
            <template x-for="item in getCartItemsArray()" :key="item.key">
                <div class="bg-gray-50 rounded-xl p-3">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0 pr-2">
                            <h4 class="font-medium text-gray-800 text-sm truncate" x-text="item.name"></h4>
                            <p class="text-xs text-gray-500"><span x-text="formatCurrency(item.price)"></span> each</p>
                        </div>
                        <button @click="removeItem(item.key)" class="text-red-400 hover:text-red-600 p-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center bg-white rounded-lg border border-gray-200">
                            <button @click="decrement(item.key)"
                                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-l-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <input type="number" 
                                :value="item.quantity"
                                min="1"
                                :max="getStock(item.key)"
                                @change="updateQty(item.key, $el.value)"
                                min="1"
                                class="w-14 h-8 text-center font-semibold text-gray-800 border-x border-gray-200 focus:outline-none focus:ring-1 focus:ring-brand-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button @click="increment(item.key)"
                                class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-r-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                            </button>
                        </div>
                        <span class="font-bold text-gray-800" x-text="formatCurrency(lineTotal(item))"></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Cart Footer -->
        <div x-show="itemCount() > 0" x-cloak class="border-t border-gray-100 p-4 space-y-4 bg-gray-50">
            <!-- Total -->
            <div class="flex items-center justify-between text-lg">
                <span class="font-medium text-gray-600">Total</span>
                <span class="text-2xl font-bold text-gray-800" x-text="formatCurrency(grandTotal())"></span>
            </div>

            <!-- Sale Date (Admin Only) -->
            <div x-show="isAdmin" class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                <label class="block text-sm font-medium text-amber-800 mb-2">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Sale Date (Backdate)
                </label>
                <input type="date" 
                       x-model="saleDate"
                       :max="new Date().toLocaleDateString('en-CA')"
                       class="w-full px-3 py-2 border border-amber-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 bg-white">
                <p class="text-xs text-amber-600 mt-1" x-show="saleDate !== new Date().toLocaleDateString('en-CA')">
                    ⚠️ Recording sale for a past date
                </p>
            </div>

            <!-- Payment Method -->
            <div class="grid grid-cols-3 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" x-model="paymentMethod" value="CASH" class="sr-only peer">
                    <div class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl transition-colors"
                         :class="paymentMethod === 'CASH' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="font-medium text-gray-700">Cash</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" x-model="paymentMethod" value="CARD" class="sr-only peer">
                    <div class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl transition-colors"
                         :class="paymentMethod === 'CARD' ? 'border-brand-500 bg-brand-50' : 'border-gray-200 hover:border-gray-300'">
                        <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span class="font-medium text-gray-700">Card</span>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" x-model="paymentMethod" value="TRANSFER" class="sr-only peer">
                    <div class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl transition-colors"
                         :class="paymentMethod === 'TRANSFER' ? 'border-violet-600 bg-violet-50' : 'border-gray-200 hover:border-gray-300'">
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        <span class="font-medium text-gray-700">Transfer</span>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 gap-3">
                <button @click="clearAllItems()"
                    class="px-4 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 font-medium transition-colors">
                    Clear Cart
                </button>
                <button @click="completeSale()"
                    class="px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    Complete Sale
                </button>
            </div>
        </div>
    </div>

    <!-- Confirm Sale Modal (Alpine-powered) -->
    <div x-show="showConfirmModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="confirm-modal"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showConfirmModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="closeConfirmModal()"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

            <div x-show="showConfirmModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-md my-8 text-left align-middle bg-white shadow-2xl rounded-2xl overflow-hidden">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-brand-500 to-brand-600 text-white p-5 text-center">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="text-xl font-bold">Confirm Sale</h3>
                    <p class="text-brand-100 text-sm">Please review before completing</p>
                </div>

                <!-- Modal Body -->
                <div class="p-5">
                    <!-- Sale Summary -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-4">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Items:</span>
                                <span class="font-semibold" x-text="itemCount() + ' product(s)'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Payment Method:</span>
                                <span class="font-semibold" 
                                      :class="{
                                          'text-green-600': paymentMethod === 'CASH',
                                          'text-brand-500': paymentMethod === 'CARD',
                                          'text-purple-600': paymentMethod === 'TRANSFER'
                                      }" 
                                      x-text="paymentMethod"></span>
                            </div>
                            <!-- Show sale date if backdating -->
                            <div x-show="isAdmin && saleDate !== new Date().toLocaleDateString('en-CA')" class="flex justify-between text-amber-600">
                                <span>Sale Date:</span>
                                <span class="font-semibold" x-text="new Date(saleDate).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="max-h-40 overflow-y-auto mb-4">
                        <table class="w-full text-sm">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-2 py-2 text-left">Item</th>
                                    <th class="px-2 py-2 text-center">Qty</th>
                                    <th class="px-2 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="item in getCartItemsArray()" :key="item.key">
                                    <tr>
                                        <td class="px-2 py-2 text-gray-800" x-text="item.name.substring(0, 20)"></td>
                                        <td class="px-2 py-2 text-center" x-text="item.quantity"></td>
                                        <td class="px-2 py-2 text-right" x-text="formatCurrency(item.price * item.quantity)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl p-4 text-center">
                        <p class="text-sm text-green-100">Total Amount</p>
                        <p class="text-3xl font-bold" x-text="formatCurrency(grandTotal())"></p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-5 bg-gray-50 border-t border-gray-100 flex gap-3">
                    <button @click="closeConfirmModal()"
                        class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 font-medium transition-colors">
                        Cancel
                    </button>
                    <button @click="confirmAndSubmit()" :disabled="isSubmitting"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 font-medium transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                        <template x-if="!isSubmitting">
                            <span>
                                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Confirm Sale
                            </span>
                        </template>
                        <template x-if="isSubmitting">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Modal -->
    @if($lastSale)
    <div x-data="{ open: @entangle('showReceipt') }"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="receipt-modal"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-sm my-8 text-left align-middle bg-white shadow-2xl rounded-2xl overflow-hidden">
                <!-- Receipt Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 text-white p-4 text-center">
                    <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h3 class="text-xl font-bold">Sale Complete!</h3>
                    <p class="text-green-100 text-sm">Transaction successful</p>
                </div>

                <!-- Printable Receipt Content -->
                <div id="receipt-content" class="p-4">
                    <!-- Store Info -->
                    <div class="text-center border-b border-dashed border-gray-300 pb-4 mb-4">
                        <img src="{{ asset('logo/logo.png') }}" alt="Logo" class="receipt-logo h-8 w-auto mx-auto mb-2">
                        <h4 class="font-bold text-lg text-gray-800">{{ config('app.name', 'SalesMgt') }}</h4>
                        <p class="text-sm text-gray-500">{{ $lastSale->location->name ?? 'Main Store' }}</p>
                        <p class="text-sm text-gray-500">{{ $lastSale->location->address ?? '' }}</p>
                        <p class="text-sm text-gray-500">{{ $lastSale->location->phone ?? '' }}</p>
                    </div>

                    <!-- Receipt Details -->
                    <div class="space-y-1 text-sm border-b border-dashed border-gray-300 pb-4 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Receipt #:</span>
                            <span class="font-mono font-semibold">{{ $lastSale->sale_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date:</span>
                            <span>{{ ($lastSale->sale_date ?? $lastSale->created_at)->format('M d, Y') }}</span>
                        </div>
                        @if($lastSale->sale_date && $lastSale->sale_date->format('Y-m-d') !== $lastSale->created_at->format('Y-m-d'))
                        <div class="flex justify-between text-amber-600">
                            <span>Recorded:</span>
                            <span class="font-medium">{{ $lastSale->created_at->format('M d, Y') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">Time:</span>
                            <span>{{ $lastSale->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cashier:</span>
                            <span>{{ $lastSale->user->name ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Payment:</span>
                            <span class="font-semibold @if($lastSale->payment_method === 'CASH') text-green-600 @elseif($lastSale->payment_method === 'CARD') text-brand-500 @else text-purple-600 @endif">
                                {{ $lastSale->payment_method }}
                            </span>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="border-b border-dashed border-gray-300 pb-4 mb-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-500 text-xs uppercase">
                                    <th class="text-left pb-2">Item</th>
                                    <th class="text-center pb-2">Qty</th>
                                    <th class="text-right pb-2">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($lastSale->items as $item)
                                <tr>
                                    <td class="py-2 pr-2">
                                        <span class="font-medium text-gray-800">{{ $item->product->name ?? 'Product' }}</span>
                                        <span class="block text-xs text-gray-400">@ ₦{{ number_format($item->unit_price, 0) }}</span>
                                    </td>
                                    <td class="py-2 text-center font-bold qty-col">{{ $item->quantity }}</td>
                                    <td class="py-2 text-right font-medium text-gray-800">₦{{ number_format($item->total, 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Total -->
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Subtotal:</span>
                            <span>₦{{ number_format($lastSale->subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                            <span>TOTAL:</span>
                            <span class="text-green-600">₦{{ number_format($lastSale->total, 0) }}</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-6 pt-4 border-t border-dashed border-gray-300">
                        <p class="text-sm text-gray-500">Thank you for your purchase!</p>
                        <p class="text-xs text-gray-400 mt-1">Please come again</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="p-4 bg-gray-50 flex gap-3">
                    <button wire:click="closeReceipt"
                        class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-100 font-medium transition-colors">
                        Close
                    </button>
                    <button type="button" 
                        x-data
                        @click="
                            const content = document.getElementById('receipt-content').innerHTML;
                            const win = window.open('', 'receipt', 'width=300,height=500');
                            win.document.write('<html><head><title>Receipt</title><style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:Courier New,monospace;font-size:11px;padding:8px;max-width:260px;margin:0 auto}.receipt-logo{max-height:50px!important;width:auto!important}.text-center{text-align:center}.text-right{text-align:right}.font-bold,.font-semibold,.font-medium{font-weight:bold}.border-b{border-bottom:1px dashed #999}.border-t{border-top:1px dashed #999}.pb-4{padding-bottom:12px}.mb-4{margin-bottom:12px}.pt-2,.pt-4{padding-top:6px}.mt-6{margin-top:16px}.mt-1{margin-top:2px}.text-lg{font-size:13px}.text-sm{font-size:10px}.text-xs{font-size:9px}.text-gray-500,.text-gray-400{color:#666}.text-gray-800{color:#333}.text-green-600{color:#059669}.text-brand-500{color:#2563eb}.space-y-1>*+*{margin-top:2px}.space-y-2>*+*{margin-top:4px}.flex{display:flex}.justify-between{justify-content:space-between}.divide-y>*+*{border-top:1px solid #eee}.block{display:block}.py-2{padding:4px 0}.pr-2{padding-right:4px}table{width:100%;border-collapse:collapse}th,td{padding:3px 0;vertical-align:top}.qty-col{font-weight:bold!important}</style></head><body>' + content + '</body></html>');
                            win.document.close();
                            win.focus();
                            setTimeout(() => { win.print(); }, 250);
                        "
                        class="flex-1 px-4 py-3 bg-brand-500 text-white rounded-xl hover:bg-brand-600 font-medium transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
