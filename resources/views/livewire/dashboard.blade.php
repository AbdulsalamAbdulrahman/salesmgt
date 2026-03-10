<div class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="mb-10">
            <h1 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 via-gray-700 to-gray-900 tracking-tight">
                Welcome back, {{ Auth::user()->name }}!
            </h1>
            <p class="text-gray-500 mt-2 text-lg font-light">Here's what's happening with your business today</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Today's Sales -->
            <a href="{{ route('sales.index') }}" wire:navigate
               class="group bg-gradient-to-br from-brand-500 to-brand-600 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-brand-100 text-sm font-medium uppercase tracking-wider">Today's Sales</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format($todaySales, 0) }}</p>
                <p class="text-brand-200 text-sm mt-1">{{ $todaySalesCount }} transactions</p>
                <!-- Payment Method Breakdown -->
                <div class="mt-3 pt-3 border-t border-white/20 space-y-1">
                    <div class="flex justify-between text-xs">
                        <span class="text-brand-200">Cash ({{ $todayCashCount }})</span>
                        <span class="text-white font-semibold">₦{{ number_format($todayCashSales, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-brand-200">Transfer ({{ $todayTransferCount }})</span>
                        <span class="text-white font-semibold">₦{{ number_format($todayTransferSales, 0) }}</span>
                    </div>
                    @if($todayCardCount > 0)
                    <div class="flex justify-between text-xs">
                        <span class="text-brand-200">Card ({{ $todayCardCount }})</span>
                        <span class="text-white font-semibold">₦{{ number_format($todayCardSales, 0) }}</span>
                    </div>
                    @endif
                </div>
                <div class="mt-3 flex items-center text-brand-100 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View sales history</span>
                </div>
            </a>

            @if($isAdmin)
            <!-- This Month (Admin only) -->
            <a href="{{ route('reports.index') }}" wire:navigate
               class="group bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-emerald-100 text-sm font-medium uppercase tracking-wider">This Month</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format($monthSales, 0) }}</p>
                <p class="text-emerald-200 text-sm mt-1">Monthly revenue</p>
                <div class="mt-3 flex items-center text-emerald-100 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View reports</span>
                </div>
            </a>
            @endif

            <!-- Closing Balance (Sales - Expenses) -->
            <div class="group bg-gradient-to-br {{ $closingBalance >= 0 ? 'from-blue-500 to-indigo-600' : 'from-red-500 to-rose-600' }} rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="{{ $closingBalance >= 0 ? 'text-blue-100' : 'text-red-100' }} text-sm font-medium uppercase tracking-wider">Closing Balance</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format(abs($closingBalance), 0) }}</p>
                <div class="mt-3 flex items-center {{ $closingBalance >= 0 ? 'text-blue-100' : 'text-red-100' }} text-xs">
                    <span>Sales: ₦{{ number_format($todaySales, 0) }} | Expenses: ₦{{ number_format($todayExpenses, 0) }}</span>
                </div>
            </div>

            @if($isAdmin)
            <!-- Total Products (Admin only) -->
            <a href="{{ route('products.index') }}" wire:navigate
               class="group bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-amber-100 text-sm font-medium uppercase tracking-wider">Total Products</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white">{{ $totalProducts }}</p>
                <p class="text-amber-200 text-sm mt-1">Active products</p>
                <div class="mt-3 flex items-center text-amber-100 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">Manage products</span>
                </div>
            </a>
            @endif

            @if($isAdmin)
            <!-- Low Stock Alert (Admin only) -->
            <a href="{{ route('products.index') }}?stock=low" wire:navigate
               class="group bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer relative overflow-hidden">
                @if($lowStockProducts > 0)
                <div class="absolute top-3 right-3">
                    <span class="flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-white"></span>
                    </span>
                </div>
                @endif
                <div class="flex items-center justify-between mb-3">
                    <p class="text-rose-100 text-sm font-medium uppercase tracking-wider">Low Stock</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white">{{ $lowStockProducts }}</p>
                @if($lowStockProducts > 0)
                    <p class="text-rose-200 text-sm mt-1 font-medium">⚠️ Needs attention</p>
                @else
                    <p class="text-rose-200 text-sm mt-1">All stocked up!</p>
                @endif
                <div class="mt-3 flex items-center text-rose-100 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View low stock items</span>
                </div>
            </a>
            @endif

            @if($isAdmin)
            <!-- Stock Valuation (Admin only) -->
            <a href="{{ route('reports.index') }}?type=stock" wire:navigate
               class="group bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wider">Stock Value</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="text-purple-200 text-xs">At Cost:</span>
                        <span class="text-lg font-bold text-white break-all">₦{{ number_format($stockValueAtCost, 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-purple-200 text-xs">At Selling:</span>
                        <span class="text-sm font-semibold text-purple-100 break-all">₦{{ number_format($stockValueAtSelling, 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-1 border-t border-purple-400/30">
                        <span class="text-purple-200 text-xs">Expected Profit:</span>
                        <span class="text-sm font-semibold {{ $stockExpectedProfit >= 0 ? 'text-green-300' : 'text-red-300' }} break-all">₦{{ number_format($stockExpectedProfit, 0) }}</span>
                    </div>
                </div>
                <div class="mt-3 flex items-center text-purple-100 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View detailed valuation</span>
                </div>
            </a>
            @endif

            @if($isAdmin)
            <!-- Today's Profit (Admin only) -->
            <a href="{{ route('reports.index') }}" wire:navigate
               class="group bg-gradient-to-br {{ $todayProfit >= 0 ? 'from-teal-500 to-cyan-600' : 'from-red-600 to-rose-700' }} rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="{{ $todayProfit >= 0 ? 'text-teal-100' : 'text-red-100' }} text-sm font-medium uppercase tracking-wider">Today's Profit</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format(abs($todayProfit), 0) }}</p>
                <p class="{{ $todayProfit >= 0 ? 'text-teal-200' : 'text-red-200' }} text-xs mt-1">Revenue - Cost - Expenses</p>
                <div class="mt-3 flex items-center {{ $todayProfit >= 0 ? 'text-teal-100' : 'text-red-100' }} text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View profit report</span>
                </div>
            </a>
            @endif

            @if($isAdmin)
            <!-- Month Profit (Admin only) -->
            <a href="{{ route('reports.index') }}" wire:navigate
               class="group bg-gradient-to-br {{ $monthProfit >= 0 ? 'from-green-500 to-emerald-600' : 'from-red-600 to-rose-700' }} rounded-2xl shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 p-5 cursor-pointer">
                <div class="flex items-center justify-between mb-3">
                    <p class="{{ $monthProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-sm font-medium uppercase tracking-wider">Month Profit</p>
                    <div class="bg-white/20 rounded-xl p-2 group-hover:bg-white/30 transition-colors">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format(abs($monthProfit), 0) }}</p>
                <p class="{{ $monthProfit >= 0 ? 'text-green-200' : 'text-red-200' }} text-xs mt-1">Net profit this month</p>
                <div class="mt-3 flex items-center {{ $monthProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-sm">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                    <span class="group-hover:underline">View detailed report</span>
                </div>
            </a>
            @endif
        </div>

        <!-- Recent Sales -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Recent Sales</h2>
                        <p class="text-sm text-gray-500 mt-1">Latest transactions across all locations</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-brand-600 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                        View All
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sale #</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                            @if($isAdmin)
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Cashier</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Attendant</th>
                            @endif
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentSales as $sale)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ $sale->sale_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $sale->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $sale->created_at->format('h:i A') }}</div>
                            </td>
                            @if($isAdmin)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-brand-100 flex items-center justify-center">
                                        <span class="text-xs font-medium text-brand-700">{{ substr($sale->user->name, 0, 2) }}</span>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-700">{{ $sale->user->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($sale->attendant)
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <span class="text-xs font-medium text-emerald-700">{{ substr($sale->attendant->name, 0, 2) }}</span>
                                    </div>
                                    <span class="ml-2 text-sm text-gray-700">{{ $sale->attendant->name }}</span>
                                </div>
                                @else
                                <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ $sale->items->sum('quantity') }} pcs
                                    </span>
                                    <span class="text-xs text-gray-500 mt-1 pl-1">{{ $sale->items->count() }} product{{ $sale->items->count() > 1 ? 's' : '' }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $sale->payment_method === 'CASH' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                                    @if($sale->payment_method === 'CASH')
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                                    </svg>
                                    @else
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4zM2 9v5a2 2 0 002 2h12a2 2 0 002-2V9H2zm6 2a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                                    </svg>
                                    @endif
                                    {{ $sale->payment_method }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900">₦{{ number_format($sale->total, 2) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 7 : 5 }}" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-gray-100 rounded-full p-4 mb-4">
                                        <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 font-medium">No sales recorded yet</p>
                                    <p class="text-gray-400 text-sm mt-1">Start making sales to see them here</p>
                                    <a href="{{ route('sales.create') }}" class="mt-6 inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Create First Sale
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
