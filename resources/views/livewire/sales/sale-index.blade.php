<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-2xl font-bold text-gray-800">Sales History</h2>
        <p class="text-gray-500 text-sm mt-1">View and manage all sales transactions</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5"
        x-data="{
            filters: {
                search: '{{ $search }}',
                startDate: '{{ $startDate }}',
                endDate: '{{ $endDate }}',
                paymentMethod: '{{ $paymentMethodFilter }}'
            },
            init() {
                this.$watch('filters', () => {
                    this.applyFilters();
                });
            },
            applyFilters() {
                $wire.applyFilters(this.filters);
            }
        }"
    >
        <div class="flex flex-wrap items-center gap-4">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" x-model.debounce.500ms="filters.search"
                    placeholder="Search by sale number..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Date Range -->
            <div class="flex items-center gap-2">
                <input type="date" x-model="filters.startDate" 
                    class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                <span class="text-gray-400">to</span>
                <input type="date" x-model="filters.endDate" 
                    class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
            </div>

            <!-- Payment Filter -->
            <select x-model="filters.paymentMethod" 
                class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                <option value="">All Payments</option>
                <option value="CASH">Cash</option>
                <option value="CARD">Card</option>
                <option value="TRANSFER">Transfer</option>
            </select>
        </div>
    </div>

    <!-- Sales List -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" x-data="{ expanded: null }">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sale #</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cashier</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendant</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Items</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono font-semibold text-brand-500">{{ $sale->sale_number }}</span>
                                @if($sale->sale_date && $sale->sale_date->format('Y-m-d') !== $sale->created_at->format('Y-m-d'))
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded text-[10px] font-medium" title="Backdated sale - recorded on {{ $sale->created_at->format('M d, Y') }}">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Backdated
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ ($sale->sale_date ?? $sale->created_at)->format('M d, Y') }}<br>
                                <span class="text-xs text-gray-400">{{ $sale->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">{{ $sale->user->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                @if($sale->attendant)
                                    <span class="inline-flex items-center px-2.5 py-1 bg-teal-50 text-teal-700 rounded-full text-xs font-medium">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $sale->attendant->name }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $sale->location->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium inline-flex items-center w-fit">
                                        {{ $sale->items->sum('quantity') }} pcs
                                    </span>
                                    <span class="text-xs text-gray-500 mt-1 pl-1">{{ $sale->items->count() }} product{{ $sale->items->count() > 1 ? 's' : '' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 rounded-full text-xs font-semibold 
                                    @if($sale->payment_method === 'CASH') bg-amber-100 text-amber-700
                                    @elseif($sale->payment_method === 'CARD') bg-brand-100 text-brand-600
                                    @else bg-purple-100 text-purple-700
                                    @endif">
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-gray-800">₦{{ number_format($sale->total, 0) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button wire:click="showReceipt({{ $sale->id }})"
                                        class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                        title="Reprint Receipt">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                    </button>
                                    <button @click="expanded = (expanded === {{ $sale->id }} ? null : {{ $sale->id }})"
                                        class="p-2 text-gray-400 hover:text-brand-500 hover:bg-brand-50 rounded-lg transition-colors"
                                        title="View Details">
                                        <svg class="w-5 h-5 transition-transform" 
                                             :class="{'rotate-180': expanded === {{ $sale->id }}}"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr class="bg-gray-50" x-show="expanded === {{ $sale->id }}" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform -translate-y-2" x-transition:enter-end="opacity-100 transform translate-y-0">
                                <td colspan="9" class="px-6 py-4">
                                    <div class="bg-white rounded-xl border border-gray-200 p-4">
                                        <h4 class="font-semibold text-gray-800 mb-3 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            Sale Items
                                        </h4>
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead class="bg-gray-100 rounded-lg">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left font-medium text-gray-600">Product</th>
                                                        <th class="px-4 py-2 text-right font-medium text-gray-600">Unit Price</th>
                                                        <th class="px-4 py-2 text-center font-medium text-gray-600">Qty</th>
                                                        <th class="px-4 py-2 text-right font-medium text-gray-600">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach($sale->items as $item)
                                                        <tr>
                                                            <td class="px-4 py-2 text-gray-800">{{ $item->product->name ?? 'Deleted Product' }}</td>
                                                            <td class="px-4 py-2 text-right text-gray-600">₦{{ number_format($item->unit_price, 0) }}</td>
                                                            <td class="px-4 py-2 text-center">
                                                                <span class="px-2 py-0.5 bg-brand-100 text-brand-600 rounded font-medium">{{ $item->quantity }}</span>
                                                            </td>
                                                            <td class="px-4 py-2 text-right font-semibold text-gray-800">₦{{ number_format($item->total, 0) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot class="bg-gray-50">
                                                    <tr>
                                                        <td colspan="3" class="px-4 py-2 text-right font-semibold text-gray-700">Grand Total:</td>
                                                        <td class="px-4 py-2 text-right font-bold text-green-600 text-lg">₦{{ number_format($sale->total, 0) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">No sales found</p>
                                <p class="text-gray-400 text-sm mt-1">Sales will appear here once transactions are made</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($sales->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $sales->links() }}
            </div>
        @endif
    </div>

    <!-- Receipt Modal -->
    @if($selectedSale)
    <div x-data="{ open: @entangle('showReceiptModal') }"
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
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                 @click="$wire.closeReceipt()"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-sm my-8 text-left align-middle bg-white shadow-2xl rounded-2xl overflow-hidden">
                
                <!-- Receipt Header -->
                <div class="bg-gradient-to-r from-brand-500 to-brand-600 text-white p-4 text-center">
                    <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <h3 class="text-lg font-bold">Reprint Receipt</h3>
                    <p class="text-brand-100 text-sm">{{ $selectedSale->sale_number }}</p>
                </div>

                <!-- Printable Receipt Content -->
                <div id="reprint-receipt-content" class="p-4">
                    <!-- Store Info -->
                    <div class="text-center border-b border-dashed border-gray-300 pb-4 mb-4">
                        <img src="{{ asset('logo/logo.png') }}" alt="Logo" class="receipt-logo h-8 w-auto mx-auto mb-2">
                        <h4 class="font-bold text-lg text-gray-800">{{ config('app.name', 'SalesMgt') }}</h4>
                        <p class="text-sm text-gray-500">{{ $selectedSale->location->name ?? 'Main Store' }}</p>
                        <p class="text-sm text-gray-500">{{ $selectedSale->location->address ?? '' }}</p>
                        <p class="text-sm text-gray-500">{{ $selectedSale->location->phone ?? '' }}</p>
                    </div>

                    <!-- Receipt Details -->
                    <div class="space-y-1 text-sm border-b border-dashed border-gray-300 pb-4 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Receipt #:</span>
                            <span class="font-mono font-semibold">{{ $selectedSale->sale_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date:</span>
                            <span>{{ ($selectedSale->sale_date ?? $selectedSale->created_at)->format('M d, Y') }}</span>
                        </div>
                        @if($selectedSale->sale_date && $selectedSale->sale_date->format('Y-m-d') !== $selectedSale->created_at->format('Y-m-d'))
                        <div class="flex justify-between text-amber-600">
                            <span>Recorded:</span>
                            <span class="font-medium">{{ $selectedSale->created_at->format('M d, Y') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">Time:</span>
                            <span>{{ $selectedSale->created_at->format('h:i A') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cashier:</span>
                            <span>{{ $selectedSale->user->name ?? 'N/A' }}</span>
                        </div>
                        @if($selectedSale->attendant)
                        <div class="flex justify-between">
                            <span class="text-gray-500">Attendant:</span>
                            <span>{{ $selectedSale->attendant->name }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-gray-500">Payment:</span>
                            <span class="font-semibold @if($selectedSale->payment_method === 'CASH') text-green-600 @elseif($selectedSale->payment_method === 'CARD') text-brand-500 @else text-purple-600 @endif">
                                {{ $selectedSale->payment_method }}
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
                                @foreach($selectedSale->items as $item)
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
                            <span>₦{{ number_format($selectedSale->subtotal, 0) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-2">
                            <span>TOTAL:</span>
                            <span class="text-green-600">₦{{ number_format($selectedSale->total, 0) }}</span>
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
                            const content = document.getElementById('reprint-receipt-content').innerHTML;
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
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
