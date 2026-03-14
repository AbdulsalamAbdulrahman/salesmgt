<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Reports</h2>
                <p class="text-gray-500 text-sm mt-1">Analyze sales performance and inventory data</p>
            </div>
            <div class="flex flex-wrap items-center gap-3"
                x-data="{
                    filters: {
                        reportType: '{{ $reportType }}',
                        startDate: '{{ $startDate }}',
                        endDate: '{{ $endDate }}',
                        locationId: '{{ $locationId }}'
                    },
                    init() {
                        this.$watch('filters', () => this.applyFilters());
                    },
                    applyFilters() {
                        $wire.applyFilters(this.filters);
                    }
                }"
            >
                <!-- Report Type -->
                <select x-model="filters.reportType" 
                    class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50 font-medium">
                    <option value="sales">📊 Sales Report</option>
                    <option value="products">📦 Top Products</option>
                    <option value="profit">💰 Profit Report</option>
                    <option value="staff">👥 Staff Summary</option>
                    <option value="stock">🏪 Stock Valuation</option>
                    <option value="wife_shop">🏠 POSshop</option>
                </select>

                <!-- Date Range -->
                <div class="flex items-center gap-2">
                    <input type="date" x-model="filters.startDate" 
                        class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                    <span class="text-gray-400">to</span>
                    <input type="date" x-model="filters.endDate" 
                        class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                </div>

                <!-- Location Filter (Admin Only) -->
                @if(Auth::user()->isAdmin())
                <select x-model="filters.locationId" 
                    class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500 bg-gray-50">
                    <option value="">All Locations</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @endif

                <!-- Export -->
                <button wire:click="exportReport" 
                    class="px-4 py-2.5 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export
                </button>
            </div>
        </div>
    </div>

    @if(session('message'))
        <div class="p-4 bg-brand-50 border border-brand-200 text-brand-600 rounded-xl flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            {{ session('message') }}
        </div>
    @endif

    <!-- Summary Stats -->
    @if($reportType === 'sales')
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4">
        <div class="bg-gradient-to-br from-brand-400 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Sales</p>
            <p class="text-xl font-bold mt-1">{{ count($reportData) }}</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 text-white">
            <p class="text-green-100 text-xs font-medium uppercase">Total Revenue</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(collect($reportData)->sum('total'), 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-4 text-white">
            <p class="text-amber-100 text-xs font-medium uppercase">Cash Sales</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(collect($reportData)->where('payment_method', 'CASH')->sum('total'), 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-brand-300 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Card Sales</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(collect($reportData)->where('payment_method', 'CARD')->sum('total'), 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-4 text-white">
            <p class="text-purple-100 text-xs font-medium uppercase">Transfer Sales</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(collect($reportData)->where('payment_method', 'TRANSFER')->sum('total'), 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl p-4 text-white">
            <p class="text-rose-100 text-xs font-medium uppercase">Total Expenses</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($totalExpenses, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br {{ $closingBalance >= 0 ? 'from-teal-500 to-cyan-600' : 'from-red-600 to-rose-700' }} rounded-2xl p-4 text-white">
            <p class="{{ $closingBalance >= 0 ? 'text-teal-100' : 'text-red-100' }} text-xs font-medium uppercase">Closing Balance</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(abs($closingBalance), 0) }}</p>
        </div>
    </div>
    @elseif($reportType === 'profit')
    @php
        $totalRevenue = 0;
        $totalCost = 0;
        $totalGrossProfit = 0;
        foreach($reportData as $sale) {
            $revenue = $sale->total;
            $cost = $sale->items->sum(fn($item) => $item->cost_price * $item->quantity);
            $totalRevenue += $revenue;
            $totalCost += $cost;
            $totalGrossProfit += ($revenue - $cost);
        }
        // Get total expenses for the period
        $periodExpenses = \App\Models\Expense::query()
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');
        // True Profit = Revenue - Cost - Expenses
        $totalProfit = $totalGrossProfit - $periodExpenses;
        $overallMargin = $totalRevenue > 0 ? ($totalProfit / $totalRevenue) * 100 : 0;
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-gradient-to-br from-brand-400 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Sales</p>
            <p class="text-xl font-bold mt-1">{{ count($reportData) }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
            <p class="text-blue-100 text-xs font-medium uppercase">Total Revenue</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($totalRevenue, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-gray-500 to-gray-600 rounded-2xl p-4 text-white">
            <p class="text-gray-200 text-xs font-medium uppercase">Total Cost</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($totalCost, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl p-4 text-white">
            <p class="text-rose-100 text-xs font-medium uppercase">Total Expenses</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($periodExpenses, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br {{ $totalProfit >= 0 ? 'from-emerald-500 to-green-600' : 'from-red-500 to-rose-600' }} rounded-2xl p-4 text-white">
            <p class="{{ $totalProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-xs font-medium uppercase">Net Profit</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($totalProfit, 0) }}</p>
            <p class="{{ $totalProfit >= 0 ? 'text-green-200' : 'text-red-200' }} text-xs mt-1">Rev - Cost - Exp</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-4 text-white">
            <p class="text-green-100 text-xs font-medium uppercase">Profit Margin</p>
            <p class="text-xl font-bold mt-1">{{ number_format($overallMargin, 1) }}%</p>
        </div>
    </div>
    @elseif($reportType === 'staff')
    @php
        $grandTotalSales = collect($reportData)->sum('total_sales');
        $grandTotalExpenses = collect($reportData)->sum('total_expenses');
        $grandNetBalance = $grandTotalSales - $grandTotalExpenses;
        $totalSalesCount = collect($reportData)->sum('sales_count');
        $grandCashSales = collect($reportData)->sum('cash_sales');
        $grandTransferSales = collect($reportData)->sum('transfer_sales');
        $grandCardSales = collect($reportData)->sum('card_sales');
        $grandCashCount = collect($reportData)->sum('cash_count');
        $grandTransferCount = collect($reportData)->sum('transfer_count');
        $grandCardCount = collect($reportData)->sum('card_count');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-4">
        <div class="bg-gradient-to-br from-brand-400 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Staff</p>
            <p class="text-xl font-bold mt-1">{{ count($reportData) }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
            <p class="text-blue-100 text-xs font-medium uppercase">Sales Count</p>
            <p class="text-xl font-bold mt-1">{{ number_format($totalSalesCount) }}</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 text-white">
            <p class="text-green-100 text-xs font-medium uppercase">Total Sales</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($grandTotalSales, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-4 text-white">
            <p class="text-amber-100 text-xs font-medium uppercase">Cash ({{ $grandCashCount }})</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($grandCashSales, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-4 text-white">
            <p class="text-purple-100 text-xs font-medium uppercase">Transfer ({{ $grandTransferCount }})</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($grandTransferSales, 0) }}</p>
        </div>
        @if($grandCardCount > 0)
        <div class="bg-gradient-to-br from-brand-300 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Card ({{ $grandCardCount }})</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($grandCardSales, 0) }}</p>
        </div>
        @endif
        <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl p-4 text-white">
            <p class="text-rose-100 text-xs font-medium uppercase">Total Expenses</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($grandTotalExpenses, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br {{ $grandNetBalance >= 0 ? 'from-teal-500 to-cyan-600' : 'from-red-600 to-rose-700' }} rounded-2xl p-4 text-white">
            <p class="{{ $grandNetBalance >= 0 ? 'text-teal-100' : 'text-red-100' }} text-xs font-medium uppercase">Net Balance</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format(abs($grandNetBalance), 0) }}</p>
        </div>
    </div>
    @elseif($reportType === 'stock' && $stockSummary)
    @php
        $expectedProfit = ($stockSummary->selling_value ?? 0) - ($stockSummary->cost_value ?? 0);
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-brand-400 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Products</p>
            <p class="text-xl font-bold mt-1">{{ count($reportData) }}</p>
            <p class="text-brand-200 text-sm">with stock</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
            <p class="text-blue-100 text-xs font-medium uppercase">Total Units</p>
            <p class="text-xl font-bold mt-1">{{ number_format($stockSummary->total_quantity ?? 0) }}</p>
            <p class="text-blue-200 text-sm">in stock</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl p-4 text-white">
            <p class="text-purple-100 text-xs font-medium uppercase">Value at Cost</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($stockSummary->cost_value ?? 0, 0) }}</p>
            <p class="text-purple-200 text-sm">purchase value</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 text-white">
            <p class="text-green-100 text-xs font-medium uppercase">Value at Selling</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($stockSummary->selling_value ?? 0, 0) }}</p>
            <p class="text-green-200 text-sm">retail value</p>
        </div>
    </div>
    <div class="mt-4 bg-gradient-to-r {{ $expectedProfit >= 0 ? 'from-emerald-500 to-green-600' : 'from-red-500 to-rose-600' }} rounded-2xl p-6 text-white text-center">
        <p class="{{ $expectedProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-sm font-medium uppercase">Expected Profit (If All Stock Sold)</p>
        <p class="text-3xl font-bold mt-2">₦{{ number_format($expectedProfit, 0) }}</p>
        <p class="{{ $expectedProfit >= 0 ? 'text-green-200' : 'text-red-200' }} text-sm mt-1">Selling Value - Cost Value</p>
    </div>
    @elseif($reportType === 'wife_shop' && $wifeShopSummary)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-brand-400 to-brand-500 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Days</p>
            <p class="text-xl font-bold mt-1">{{ $wifeShopSummary->totalDays }}</p>
        </div>
        <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl p-4 text-white">
            <p class="text-rose-100 text-xs font-medium uppercase">Total Expenses</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($wifeShopSummary->totalExpenses, 0) }}</p>
        </div>
        <div class="bg-gradient-to-br {{ $wifeShopSummary->totalProfit >= 0 ? 'from-emerald-500 to-green-600' : 'from-red-500 to-rose-600' }} rounded-2xl p-4 text-white">
            <p class="{{ $wifeShopSummary->totalProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-xs font-medium uppercase">Total Profit</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($wifeShopSummary->totalProfit, 0) }}</p>
            <p class="{{ $wifeShopSummary->totalProfit >= 0 ? 'text-green-200' : 'text-red-200' }} text-xs mt-1">Closing - Opening</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
            <p class="text-blue-100 text-xs font-medium uppercase">Total Txn</p>
            <p class="text-lg font-bold mt-1 break-all">₦{{ number_format($wifeShopSummary->totalTxn, 0) }}</p>
            <p class="text-blue-200 text-xs mt-1">Closing + Expenses</p>
        </div>
    </div>
    @endif

    <!-- Report Content -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @if($reportType === 'sales')
            <!-- Sales Report -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Sale #</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Cashier</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Attendant</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Payment</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Items</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $sale)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-semibold text-brand-500">{{ $sale->sale_number }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $sale->created_at->format('M d, Y H:i') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $sale->user->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-800">{{ $sale->attendant->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $sale->location->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold 
                                        @if($sale->payment_method === 'CASH') bg-amber-100 text-amber-700
                                        @elseif($sale->payment_method === 'CARD') bg-brand-100 text-brand-600
                                        @else bg-purple-100 text-purple-700
                                        @endif">
                                        {{ $sale->payment_method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div>{{ $sale->items->sum('quantity') }} pcs</div>
                                    <div class="text-xs text-gray-400">{{ $sale->items->count() }} {{ Str::plural('product', $sale->items->count()) }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-800">₦{{ number_format($sale->total, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    No sales data found for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($reportType === 'products')
            <!-- Top Products Report -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Rank</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">SKU</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Qty Sold</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $index => $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    @if($index < 3)
                                        <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold {{ $index === 0 ? 'bg-yellow-100 text-yellow-700' : ($index === 1 ? 'bg-gray-100 text-gray-700' : 'bg-orange-100 text-orange-700') }}">
                                            {{ $index + 1 }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 pl-3">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $item->product->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ $item->product->sku ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-brand-100 text-brand-600 rounded-full font-semibold">{{ number_format($item->total_quantity) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-green-600">₦{{ number_format($item->total_revenue, 0) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    No product sales data found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($reportType === 'profit')
            <!-- Profit Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Sale #</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Revenue</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Cost</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Profit</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $sale)
                            @php
                                $revenue = $sale->total;
                                $cost = $sale->items->sum(fn($item) => $item->cost_price * $item->quantity);
                                $profit = $revenue - $cost;
                                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-semibold text-brand-500">{{ $sale->sale_number }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $sale->created_at->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-right text-gray-800">₦{{ number_format($revenue, 0) }}</td>
                                <td class="px-6 py-4 text-right text-gray-500">₦{{ number_format($cost, 0) }}</td>
                                <td class="px-6 py-4 text-right font-semibold {{ $profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ₦{{ number_format($profit, 0) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        {{ number_format($margin, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    No profit data found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @elseif($reportType === 'staff')
            <!-- Staff Summary Report -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Staff</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Sales</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Cash</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Transfer</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Card</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Total Sales</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Expenses</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Net Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $index => $staff)
                            @php
                                $netBalance = $staff->total_sales - $staff->total_expenses;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-bold">
                                            {{ strtoupper(substr($staff->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $staff->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $staff->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold 
                                        {{ $staff->role === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ ucfirst($staff->role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $staff->location->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-brand-100 text-brand-600 rounded-full font-semibold">{{ number_format($staff->sales_count) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <span class="font-semibold text-green-700">₦{{ number_format($staff->cash_sales, 0) }}</span>
                                    <span class="text-xs text-gray-400">({{ $staff->cash_count }})</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <span class="font-semibold text-blue-700">₦{{ number_format($staff->transfer_sales, 0) }}</span>
                                    <span class="text-xs text-gray-400">({{ $staff->transfer_count }})</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm">
                                    <span class="font-semibold text-purple-700">₦{{ number_format($staff->card_sales, 0) }}</span>
                                    <span class="text-xs text-gray-400">({{ $staff->card_count }})</span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-green-600">₦{{ number_format($staff->total_sales, 0) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-red-600">₦{{ number_format($staff->total_expenses, 0) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold {{ $netBalance >= 0 ? 'text-teal-600' : 'text-red-600' }}">
                                        {{ $netBalance < 0 ? '-' : '' }}₦{{ number_format(abs($netBalance), 0) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                    No staff data found for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($reportData) > 0)
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        @php
                            $totalSalesCount = collect($reportData)->sum('sales_count');
                            $grandTotalSales = collect($reportData)->sum('total_sales');
                            $grandTotalExpenses = collect($reportData)->sum('total_expenses');
                            $grandNetBalance = $grandTotalSales - $grandTotalExpenses;
                            $footerCashSales = collect($reportData)->sum('cash_sales');
                            $footerTransferSales = collect($reportData)->sum('transfer_sales');
                            $footerCardSales = collect($reportData)->sum('card_sales');
                            $footerCashCount = collect($reportData)->sum('cash_count');
                            $footerTransferCount = collect($reportData)->sum('transfer_count');
                            $footerCardCount = collect($reportData)->sum('card_count');
                        @endphp
                        <tr>
                            <td colspan="3" class="px-6 py-4 font-bold text-gray-800">TOTALS</td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full font-bold">{{ number_format($totalSalesCount) }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <span class="font-bold text-green-700">₦{{ number_format($footerCashSales, 0) }}</span>
                                <span class="text-xs text-gray-400">({{ $footerCashCount }})</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <span class="font-bold text-blue-700">₦{{ number_format($footerTransferSales, 0) }}</span>
                                <span class="text-xs text-gray-400">({{ $footerTransferCount }})</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <span class="font-bold text-purple-700">₦{{ number_format($footerCardSales, 0) }}</span>
                                <span class="text-xs text-gray-400">({{ $footerCardCount }})</span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">₦{{ number_format($grandTotalSales, 0) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-red-600">₦{{ number_format($grandTotalExpenses, 0) }}</td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-lg {{ $grandNetBalance >= 0 ? 'text-teal-600' : 'text-red-600' }}">
                                    {{ $grandNetBalance < 0 ? '-' : '' }}₦{{ number_format(abs($grandNetBalance), 0) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        @elseif($reportType === 'stock')
            <!-- Stock Valuation Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Location</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase">Unit</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Stock</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Cost Price</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Sell Price</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Value (Cost)</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Value (Sell)</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Exp. Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $item->name }}</p>
                                        @if($item->sku)
                                            <p class="text-xs text-gray-500">SKU: {{ $item->sku }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->category_name ?? 'Uncategorized' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $item->location_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-center">
                                    @if($item->unit_type !== 'piece' && $item->qty_per_unit > 1)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-medium">
                                            {{ ucfirst($item->unit_type) }} ({{ $item->qty_per_unit }})
                                        </span>
                                    @else
                                        <span class="text-gray-500 text-sm">Piece</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="px-3 py-1 bg-brand-100 text-brand-600 rounded-full font-semibold">{{ number_format($item->quantity) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-600">₦{{ number_format($item->cost_price, 0) }}</td>
                                <td class="px-6 py-4 text-right text-sm text-gray-800 font-medium">₦{{ number_format($item->selling_price, 0) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-purple-600">₦{{ number_format($item->cost_value, 0) }}</td>
                                <td class="px-6 py-4 text-right font-semibold text-blue-600">₦{{ number_format($item->selling_value, 0) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-bold {{ $item->expected_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        ₦{{ number_format($item->expected_profit, 0) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    No stock data found for the selected filters
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($reportData) > 0 && $stockSummary)
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        @php
                            $totalExpProfit = ($stockSummary->selling_value ?? 0) - ($stockSummary->cost_value ?? 0);
                        @endphp
                        <tr>
                            <td colspan="4" class="px-6 py-4 font-bold text-gray-800">TOTALS</td>
                            <td class="px-6 py-4 text-right">
                                <span class="px-3 py-1 bg-gray-200 text-gray-700 rounded-full font-bold">{{ number_format($stockSummary->total_quantity ?? 0) }}</span>
                            </td>
                            <td colspan="2" class="px-6 py-4"></td>
                            <td class="px-6 py-4 text-right font-bold text-purple-600">₦{{ number_format($stockSummary->cost_value ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-right font-bold text-blue-600">₦{{ number_format($stockSummary->selling_value ?? 0, 0) }}</td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-bold text-lg {{ $totalExpProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    ₦{{ number_format($totalExpProfit, 0) }}
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        @elseif($reportType === 'wife_shop')
            <!-- POSshop Daily Balances -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Opening</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Closing</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Expenses</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Profit</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Total Txn</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData as $balance)
                            @php
                                $dayExpenses = $balance->day_expenses ?? 0;
                                $dayProfit = $balance->closing_balance !== null
                                    ? $balance->closing_balance - $balance->opening_balance
                                    : null;
                                $dayTxn = $balance->closing_balance !== null
                                    ? $balance->closing_balance + $dayExpenses
                                    : $dayExpenses;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800">{{ $balance->balance_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 text-right text-gray-800">₦{{ number_format($balance->opening_balance, 0) }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($balance->closing_balance !== null)
                                        <span class="font-semibold text-gray-800">₦{{ number_format($balance->closing_balance, 0) }}</span>
                                    @else
                                        <span class="text-amber-500 text-sm italic">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-red-600">₦{{ number_format($dayExpenses, 0) }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($dayProfit !== null)
                                        <span class="font-bold {{ $dayProfit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $dayProfit < 0 ? '-' : '' }}₦{{ number_format(abs($dayProfit), 0) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-blue-600">₦{{ number_format($dayTxn, 0) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $balance->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                    No daily balance data found for the selected period
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- POSshop Expense Details -->
            @if($wifeShopExpenses->isNotEmpty())
            <div class="border-t border-gray-100 mt-2">
                <div class="px-6 py-4 bg-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">Expense Details</h3>
                    <p class="text-sm text-gray-500">Individual expenses for the selected period</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($wifeShopExpenses as $expense)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-600">{{ $expense->expense_date->format('M d, Y') }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-800">{{ $expense->description }}</td>
                                    <td class="px-6 py-3 text-right font-semibold text-red-600">₦{{ number_format($expense->amount, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="2" class="px-6 py-3 font-bold text-gray-800">Total Expenses</td>
                                <td class="px-6 py-3 text-right font-bold text-red-600">₦{{ number_format($wifeShopExpenses->sum('amount'), 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif
        @endif
    </div>
</div>
