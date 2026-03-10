<div class="space-y-6"
    x-data="inventoryManager()"
    x-cloak>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Inventory Management</h2>
                <p class="text-gray-500 text-sm mt-1">Manage stock levels across locations</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative">
                    <input type="text" x-model="search"
                        placeholder="Search products..."
                        class="pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50 w-64">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Location Filter (Admin Only) -->
                @if(Auth::user()->isAdmin())
                <select x-model="locationId"
                    class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @endif

                <span class="text-sm text-gray-500" x-text="'Showing ' + filteredProducts().length + ' of ' + products.length + ' products'"></span>
            </div>
        </div>
    </div>

    <!-- Toast Messages -->
    <template x-if="successMsg">
        <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <span x-text="successMsg"></span>
            <button @click="successMsg = ''" class="ml-auto text-green-600 hover:text-green-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    <template x-if="errorMsg">
        <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            <span x-text="errorMsg"></span>
            <button @click="errorMsg = ''" class="ml-auto text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>

    <!-- Inventory Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="product in paginatedProducts()" :key="product.id + '-' + locationId">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
                <!-- Product Header -->
                <div class="p-4"
                     :class="{
                         'bg-red-50': getStock(product) <= 0,
                         'bg-amber-50': getStock(product) > 0 && getStock(product) <= product.low_stock_threshold,
                         'bg-gradient-to-r from-brand-50 to-brand-50': getStock(product) > product.low_stock_threshold
                     }">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-800 truncate" x-text="product.name"></h3>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="product.sku"></p>
                        </div>
                        <template x-if="getStock(product) <= 0">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Out</span>
                        </template>
                        <template x-if="getStock(product) > 0 && getStock(product) <= product.low_stock_threshold">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">Low</span>
                        </template>
                        <template x-if="getStock(product) > product.low_stock_threshold">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">OK</span>
                        </template>
                    </div>
                </div>

                <!-- Stock Info -->
                <div class="p-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Current Stock</p>
                            <p class="text-3xl font-bold"
                               :class="{
                                   'text-red-600': getStock(product) <= 0,
                                   'text-amber-600': getStock(product) > 0 && getStock(product) <= product.low_stock_threshold,
                                   'text-gray-800': getStock(product) > product.low_stock_threshold
                               }"
                               x-text="getStock(product).toLocaleString()"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Min. Stock</p>
                            <p class="text-lg font-medium text-gray-600" x-text="product.low_stock_threshold"></p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div class="h-2 rounded-full"
                             :class="{
                                 'bg-red-500': getStock(product) <= 0,
                                 'bg-amber-500': getStock(product) > 0 && getStock(product) <= product.low_stock_threshold,
                                 'bg-green-500': getStock(product) > product.low_stock_threshold
                             }"
                             :style="'width: ' + Math.min(100, product.low_stock_threshold > 0 ? (getStock(product) / (product.low_stock_threshold * 3)) * 100 : (getStock(product) > 0 ? 100 : 0)) + '%'"></div>
                    </div>

                    <!-- Actions -->
                    @if($canManageInventory)
                    <div class="flex items-center gap-2">
                        <button @click="openModal('stock_in', product)"
                            class="flex-1 px-3 py-2 text-sm font-medium text-green-700 bg-green-50 hover:bg-green-100 rounded-lg transition-colors flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            In
                        </button>
                        <button @click="openModal('stock_out', product)"
                            class="flex-1 px-3 py-2 text-sm font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded-lg transition-colors flex items-center justify-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                            Out
                        </button>
                        <button @click="openModal('adjustment', product)"
                            class="px-3 py-2 text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </template>

        <template x-if="filteredProducts().length === 0">
            <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="text-gray-500 text-lg">No products found</p>
                <p class="text-gray-400 text-sm mt-1">Try a different search term</p>
            </div>
        </template>
    </div>

    <!-- Pagination -->
    <template x-if="totalPages() > 1">
        <div class="flex justify-center items-center gap-2">
            <button @click="page = Math.max(1, page - 1)" :disabled="page <= 1"
                    class="px-3 py-2 border rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                Previous
            </button>
            <template x-for="p in pageNumbers()" :key="p">
                <button @click="page = p"
                        class="px-3 py-2 border rounded-lg text-sm"
                        :class="p === page ? 'bg-brand-500 text-white border-brand-500' : 'hover:bg-gray-50'"
                        x-text="p"></button>
            </template>
            <button @click="page = Math.min(totalPages(), page + 1)" :disabled="page >= totalPages()"
                    class="px-3 py-2 border rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                Next
            </button>
        </div>
    </template>

    <!-- Stock Operation Modal -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="modalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                 @click="modalOpen = false"></div>

            <div x-show="modalOpen"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-md p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl">
                <div class="absolute top-4 right-4">
                    <button @click="modalOpen = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center mb-6">
                    <div class="p-3 rounded-xl mr-4"
                         :class="{
                             'bg-green-100': modalType === 'stock_in',
                             'bg-red-100': modalType === 'stock_out',
                             'bg-brand-100': modalType === 'adjustment'
                         }">
                        <template x-if="modalType === 'stock_in'">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </template>
                        <template x-if="modalType === 'stock_out'">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                            </svg>
                        </template>
                        <template x-if="modalType === 'adjustment'">
                            <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </template>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800"
                            x-text="modalType === 'stock_in' ? 'Stock In' : (modalType === 'stock_out' ? 'Stock Out' : 'Stock Adjustment')">
                        </h3>
                        <p class="text-sm text-gray-500" x-text="modalProductName"></p>
                    </div>
                </div>

                <form @submit.prevent="submitMovement()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2"
                                x-text="modalType === 'adjustment' ? 'New Quantity' : 'Quantity'">
                            </label>
                            <input type="number" x-model="modalQuantity" min="0"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-lg font-semibold"
                                placeholder="Enter quantity">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes (Optional)</label>
                            <textarea x-model="modalNotes" rows="3"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500"
                                placeholder="Enter reason for this operation..."></textarea>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="modalOpen = false"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white transition-colors disabled:opacity-50"
                            :class="{
                                'bg-green-600 hover:bg-green-700': modalType === 'stock_in',
                                'bg-red-600 hover:bg-red-700': modalType === 'stock_out',
                                'bg-brand-500 hover:bg-brand-600': modalType === 'adjustment'
                            }"
                            x-text="isSubmitting ? 'Processing...' : 'Confirm'">
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function inventoryManager() {
            return {
                products: @js($products->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku ?? '',
                    'low_stock_threshold' => $p->low_stock_threshold ?? 0,
                    'stocks' => $p->inventoryStocks->mapWithKeys(fn($s) => [$s->location_id => $s->quantity])->toArray(),
                ])),
                locationId: @js((string) $defaultLocationId),
                search: '',
                page: 1,
                perPage: 16,

                modalOpen: false,
                modalType: 'stock_in',
                modalProductId: null,
                modalProductName: '',
                modalQuantity: '',
                modalNotes: '',
                isSubmitting: false,

                successMsg: '',
                errorMsg: '',

                getStock(product) {
                    return product.stocks[this.locationId] ?? 0;
                },

                filteredProducts() {
                    let term = this.search.toLowerCase().trim();
                    if (!term) return this.products;
                    return this.products.filter(p =>
                        p.name.toLowerCase().includes(term) || p.sku.toLowerCase().includes(term)
                    );
                },

                totalPages() {
                    return Math.ceil(this.filteredProducts().length / this.perPage);
                },

                paginatedProducts() {
                    let start = (this.page - 1) * this.perPage;
                    return this.filteredProducts().slice(start, start + this.perPage);
                },

                pageNumbers() {
                    let total = this.totalPages();
                    let pages = [];
                    let start = Math.max(1, this.page - 2);
                    let end = Math.min(total, this.page + 2);
                    for (let i = start; i <= end; i++) pages.push(i);
                    return pages;
                },

                openModal(type, product) {
                    this.modalType = type;
                    this.modalProductId = product.id;
                    this.modalProductName = product.name;
                    this.modalQuantity = '';
                    this.modalNotes = '';
                    this.modalOpen = true;
                },

                async submitMovement() {
                    if (this.isSubmitting) return;

                    let qty = parseInt(this.modalQuantity);
                    if (isNaN(qty) || (this.modalType !== 'adjustment' && qty < 1)) {
                        this.errorMsg = 'Please enter a valid quantity.';
                        setTimeout(() => this.errorMsg = '', 4000);
                        return;
                    }

                    this.isSubmitting = true;
                    this.errorMsg = '';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                        const response = await fetch('/api/inventory/movement', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                type: this.modalType,
                                product_id: this.modalProductId,
                                location_id: this.locationId,
                                quantity: qty,
                                notes: this.modalNotes || null,
                            }),
                        });

                        const data = await response.json();

                        if (data.success) {
                            let product = this.products.find(p => p.id === this.modalProductId);
                            if (product) {
                                product.stocks[this.locationId] = data.new_quantity;
                            }
                            this.modalOpen = false;
                            this.successMsg = data.message;
                            setTimeout(() => this.successMsg = '', 4000);
                        } else {
                            this.errorMsg = data.message || 'An error occurred.';
                            setTimeout(() => this.errorMsg = '', 5000);
                        }
                    } catch (e) {
                        this.errorMsg = 'Network error. Please try again.';
                        setTimeout(() => this.errorMsg = '', 5000);
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                init() {
                    this.$watch('search', () => this.page = 1);
                    this.$watch('locationId', () => this.page = 1);
                }
            };
        }
    </script>
</div>
