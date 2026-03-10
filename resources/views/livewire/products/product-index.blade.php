<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        x-data="{
            filters: {
                search: '{{ $search }}',
                stockFilter: '{{ $stockFilter }}'
            },
            init() {
                this.$watch('filters.search', () => this.applyFilters());
                this.$watch('filters.stockFilter', () => this.applyFilters());
            },
            applyFilters() {
                $wire.applyFilters(this.filters);
            }
        }"
    >
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Products</h1>
            @if($stockFilter === 'low')
                <p class="text-sm text-red-600 mt-1">Showing low stock items only</p>
            @endif
        </div>
        
        <div class="flex items-center gap-4">
            <div class="relative">
                <input x-model.debounce.500ms="filters.search" 
                       type="text" placeholder="Search products..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <select x-model="filters.stockFilter"
                class="py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent text-sm">
                <option value="">All Products</option>
                <option value="low">Low Stock</option>
            </select>
            
            @if(auth()->user()->role === 'admin' || auth()->user()->can_manage_inventory)
            <button wire:click="create" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                Add Product
            </button>
            @endif
        </div>
    </div>

    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('message') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($products as $product)
                    <tr class="{{ ($product->inventory_stocks_sum_quantity ?? 0) <= $product->low_stock_threshold ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-12 w-12 rounded-lg overflow-hidden bg-gray-100 mr-3">
                                    @if($product->image)
                                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="h-full w-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $product->name }}</div>
                                    @if($product->unit_type !== 'piece')
                                        <div class="text-xs text-blue-600 font-medium">{{ ucfirst($product->unit_type) }} ({{ $product->qty_per_unit }} items)</div>
                                    @endif
                                    @if($product->barcode)
                                        <div class="text-xs text-gray-500">{{ $product->barcode }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->sku }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $product->categoryRelation?->name ?? '-' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            ₦{{ number_format($product->cost_price, 2) }}
                            @if($product->unit_type !== 'piece' && $product->qty_per_unit > 1)
                                <span class="block text-xs text-blue-600">(₦{{ number_format($product->cost_per_item, 2) }}/item)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            ₦{{ number_format($product->selling_price / ($product->qty_per_unit ?? 1), 2) }}/item
                            @if($product->unit_type !== 'piece' && $product->qty_per_unit > 1)
                                <span class="block text-xs text-green-600">(₦{{ number_format($product->selling_price, 2) }} total)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ ($product->inventory_stocks_sum_quantity ?? 0) <= $product->low_stock_threshold ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                {{ $product->inventory_stocks_sum_quantity ?? 0 }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if(auth()->user()->role === 'admin' || auth()->user()->can_manage_inventory)
                            <button wire:click="edit({{ $product->id }})" class="text-brand-500 hover:text-brand-700 mr-3">Edit</button>
                            <button wire:click="confirmDelete({{ $product->id }})" class="text-red-600 hover:text-red-900">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Modal -->
    <div x-data="{ open: @entangle('showModal') }" 
         x-show="open" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         aria-labelledby="modal-title" 
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
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" 
                 @click="$wire.set('showModal', false)"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-lg p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl max-h-[90vh] overflow-y-auto">
                <div class="absolute top-4 right-4">
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center mb-6">
                    <div class="p-3 rounded-xl bg-emerald-100 mr-4">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{ $editMode ? 'Edit Product' : 'Add New Product' }}</h3>
                        <p class="text-sm text-gray-500">{{ $editMode ? 'Update product details' : 'Add a new product to inventory' }}</p>
                    </div>
                </div>
            
                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input wire:model="name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                                <input wire:model="sku" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('sku') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Barcode</label>
                                <input wire:model="barcode" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                @error('barcode') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select wire:model="category_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">Select a category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div x-data="{
                                rawValue: @entangle('cost_price'),
                                displayValue: '',
                                init() {
                                    this.displayValue = this.formatDisplay(this.rawValue);
                                    this.$watch('rawValue', (val) => {
                                        if (document.activeElement !== this.$refs.input) {
                                            this.displayValue = this.formatDisplay(val);
                                        }
                                    });
                                },
                                formatDisplay(val) {
                                    if (!val && val !== 0) return '';
                                    return new Intl.NumberFormat('en-NG').format(val);
                                },
                                handleInput(e) {
                                    let val = e.target.value.replace(/[^0-9.]/g, '');
                                    let parts = val.split('.');
                                    if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
                                    this.rawValue = val ? parseFloat(val) : '';
                                    this.displayValue = val ? new Intl.NumberFormat('en-NG').format(parseFloat(val)) : '';
                                },
                                handleBlur() {
                                    this.displayValue = this.formatDisplay(this.rawValue);
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Total Cost Price (₦) *</label>
                                <input x-ref="input" type="text" inputmode="decimal" 
                                    x-model="displayValue" 
                                    @input="handleInput($event)"
                                    @blur="handleBlur()"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="0">
                                <p class="text-xs text-gray-500 mt-1">Total cost for ALL units purchased</p>
                                @error('cost_price') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div x-data="{
                                rawValue: @entangle('selling_price_per_item'),
                                displayValue: '',
                                init() {
                                    this.displayValue = this.formatDisplay(this.rawValue);
                                    this.$watch('rawValue', (val) => {
                                        if (document.activeElement !== this.$refs.input) {
                                            this.displayValue = this.formatDisplay(val);
                                        }
                                    });
                                },
                                formatDisplay(val) {
                                    if (!val && val !== 0) return '';
                                    return new Intl.NumberFormat('en-NG').format(val);
                                },
                                handleInput(e) {
                                    let val = e.target.value.replace(/[^0-9.]/g, '');
                                    let parts = val.split('.');
                                    if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
                                    this.rawValue = val ? parseFloat(val) : '';
                                    this.displayValue = val ? new Intl.NumberFormat('en-NG').format(parseFloat(val)) : '';
                                },
                                handleBlur() {
                                    this.displayValue = this.formatDisplay(this.rawValue);
                                }
                            }">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price per Item (₦) *</label>
                                <input x-ref="input" type="text" inputmode="decimal"
                                    x-model="displayValue"
                                    @input="handleInput($event)"
                                    @blur="handleBlur()"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="0">
                                <p class="text-xs text-gray-500 mt-1">Price you'll sell each piece for</p>
                                @error('selling_price_per_item') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <!-- Unit Type & Quantity -->
                        <div class="grid grid-cols-2 gap-4" x-data="{ 
                            unitType: @entangle('unit_type'), 
                            qtyPerUnit: @entangle('qty_per_unit'),
                            numberOfUnits: @entangle('number_of_units'),
                            costPrice: @entangle('cost_price'),
                            sellingPricePerItem: @entangle('selling_price_per_item'),
                            formatNum(num) {
                                return new Intl.NumberFormat('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
                            },
                            get totalItems() {
                                return parseInt(this.numberOfUnits || 1) * parseInt(this.qtyPerUnit || 1);
                            },
                            get costPerUnitRaw() {
                                if (!this.costPrice || !this.numberOfUnits || this.numberOfUnits < 1) return 0;
                                return parseFloat(this.costPrice) / parseInt(this.numberOfUnits);
                            },
                            get costPerUnit() {
                                return this.formatNum(this.costPerUnitRaw);
                            },
                            get costPerItemRaw() {
                                if (!this.costPrice || !this.totalItems || this.totalItems < 1) return 0;
                                return parseFloat(this.costPrice) / this.totalItems;
                            },
                            get costPerItem() {
                                return this.formatNum(this.costPerItemRaw);
                            },
                            get totalSellingPriceRaw() {
                                if (!this.sellingPricePerItem || !this.qtyPerUnit || this.qtyPerUnit < 1) return 0;
                                return parseFloat(this.sellingPricePerItem) * parseInt(this.qtyPerUnit);
                            },
                            get totalSellingPrice() {
                                return this.formatNum(this.totalSellingPriceRaw);
                            },
                            get profitPerItemRaw() {
                                if (!this.sellingPricePerItem || !this.costPerItemRaw) return 0;
                                return parseFloat(this.sellingPricePerItem) - this.costPerItemRaw;
                            },
                            get profitPerItem() {
                                return this.formatNum(this.profitPerItemRaw);
                            },
                            get totalProfitPerUnitRaw() {
                                return this.totalSellingPriceRaw - this.costPerUnitRaw;
                            },
                            get totalProfitPerUnit() {
                                return this.formatNum(this.totalProfitPerUnitRaw);
                            },
                            get grandTotalProfitRaw() {
                                return this.profitPerItemRaw * this.totalItems;
                            },
                            get grandTotalProfit() {
                                return this.formatNum(this.grandTotalProfitRaw);
                            }
                        }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Type</label>
                                <select wire:model.live="unit_type" x-model="unitType" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="piece">Piece (Single Item)</option>
                                    <option value="pack">Pack</option>
                                    <option value="carton">Carton</option>
                                    <option value="box">Box</option>
                                    <option value="dozen">Dozen</option>
                                </select>
                                @error('unit_type') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Qty per <span x-text="unitType.charAt(0).toUpperCase() + unitType.slice(1)"></span>
                                </label>
                                <input wire:model.live="qty_per_unit" x-model="qtyPerUnit" type="number" min="1" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    :disabled="unitType === 'piece'">
                                @error('qty_per_unit') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <!-- Number of units purchased (only for non-piece) -->
                            <template x-if="unitType !== 'piece'">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Number of <span x-text="unitType.charAt(0).toUpperCase() + unitType.slice(1) + 's'"></span> Purchased
                                    </label>
                                    <input wire:model.live="number_of_units" x-model="numberOfUnits" type="number" min="1" 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                        placeholder="How many packs/cartons did you buy?">
                                    <p class="text-xs text-gray-500 mt-1">Total individual items: <span x-text="totalItems" class="font-semibold"></span></p>
                                </div>
                            </template>
                            
                            <!-- Auto-calculated values display -->
                            <template x-if="unitType !== 'piece' && qtyPerUnit > 1">
                                <div class="col-span-2 bg-blue-50 border border-blue-200 rounded-xl p-3 space-y-2">
                                    <div class="text-sm font-medium text-blue-800 mb-2">
                                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                        Breakdown: <span x-text="numberOfUnits"></span> <span x-text="unitType"></span>(s) &times; <span x-text="qtyPerUnit"></span> items = <span x-text="totalItems" class="font-bold"></span> total items
                                    </div>
                                    <!-- Warning if selling price not set -->
                                    <template x-if="!sellingPricePerItem || parseFloat(sellingPricePerItem) <= 0">
                                        <div class="bg-amber-100 border border-amber-300 text-amber-800 px-3 py-2 rounded-lg text-xs">
                                            Enter a selling price per item above to see profit calculations
                                        </div>
                                    </template>
                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="bg-white p-2 rounded-lg">
                                            <span class="text-gray-500 block text-xs">Cost per <span x-text="unitType.charAt(0).toUpperCase() + unitType.slice(1)"></span></span>
                                            <span class="font-semibold text-gray-800">₦<span x-text="costPerUnit"></span></span>
                                        </div>
                                        <div class="bg-white p-2 rounded-lg">
                                            <span class="text-gray-500 block text-xs">Cost per Item</span>
                                            <span class="font-semibold text-gray-800">₦<span x-text="costPerItem"></span></span>
                                        </div>
                                    </div>
                                    <template x-if="sellingPricePerItem && parseFloat(sellingPricePerItem) > 0">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div class="bg-white p-2 rounded-lg">
                                                <span class="text-gray-500 block text-xs">Profit per Item</span>
                                                <span class="font-semibold" :class="profitPerItemRaw >= 0 ? 'text-green-600' : 'text-red-600'">₦<span x-text="profitPerItem"></span></span>
                                            </div>
                                            <div class="bg-green-50 p-2 rounded-lg border border-green-200">
                                                <span class="text-green-700 block text-xs">Sell Value (per <span x-text="unitType"></span>)</span>
                                                <span class="font-bold text-green-800">₦<span x-text="totalSellingPrice"></span></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="sellingPricePerItem && parseFloat(sellingPricePerItem) > 0">
                                        <div class="grid grid-cols-2 gap-3 text-sm">
                                            <div class="bg-green-50 p-2 rounded-lg border border-green-200">
                                                <span class="text-green-700 block text-xs">Profit per <span x-text="unitType.charAt(0).toUpperCase() + unitType.slice(1)"></span></span>
                                                <span class="font-bold" :class="totalProfitPerUnitRaw >= 0 ? 'text-green-800' : 'text-red-600'">₦<span x-text="totalProfitPerUnit"></span></span>
                                            </div>
                                            <div class="bg-green-50 p-2 rounded-lg border border-green-200">
                                                <span class="text-green-700 block text-xs">Total Profit (all <span x-text="numberOfUnits"></span> <span x-text="unitType"></span>s)</span>
                                                <span class="font-bold" :class="grandTotalProfitRaw >= 0 ? 'text-green-800' : 'text-red-600'">₦<span x-text="grandTotalProfit"></span></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Low Stock Alert</label>
                                <input wire:model="low_stock_threshold" type="number" min="0" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            
                            <div class="flex items-center pt-8">
                                <label class="flex items-center cursor-pointer">
                                    <input wire:model="is_active" type="checkbox" class="h-5 w-5 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                    <span class="ml-2 text-sm text-gray-700">Active Product</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea wire:model="description" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" placeholder="Optional product description..."></textarea>
                        </div>
                        
                        <!-- Image Upload -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                            <div class="flex items-start gap-4">
                                <div class="flex-1">
                                    <input wire:model="image" type="file" accept="image/*" 
                                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                    <p class="text-xs text-gray-500 mt-1">Max 2MB. Supported: JPG, PNG, GIF</p>
                                    @error('image') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <!-- Image Preview -->
                                <div class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center overflow-hidden bg-gray-50">
                                    @if($image)
                                        <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                    @elseif($existingImage)
                                        <img src="{{ asset('storage/' . $existingImage) }}" alt="Current" class="w-full h-full object-cover">
                                    @else
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white bg-emerald-600 hover:bg-emerald-700 transition-colors">
                            {{ $editMode ? 'Update Product' : 'Add Product' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-data="{ open: @entangle('showDeleteModal') }"
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
                 @click="$wire.set('showDeleteModal', false)"></div>

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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Product</h3>
                    <p class="text-gray-500 text-center mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
                    
                    <div class="flex justify-center space-x-3">
                        <button wire:click="$set('showDeleteModal', false)" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="delete" 
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
