<div class="py-8 lg:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="mb-10">
            <h1
                class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-gray-900 via-gray-700 to-gray-900 tracking-tight">
                POS Shop
            </h1>
            <p class="text-gray-500 mt-2 text-lg font-light">Daily balance tracking, profits & expenses</p>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('message'))
            <div
                class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ session('message') }}
            </div>
        @endif

        <!-- Date Selector -->
        <div class="mb-6">
            <div class="bg-white rounded-xl shadow-sm p-4 inline-flex items-center gap-3">
                <label for="selectedDate" class="text-sm font-medium text-gray-700">Date:</label>
                <input type="date" wire:model.live="selectedDate" id="selectedDate"
                    class="rounded-lg border-gray-300 text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Opening Balance -->
            <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-blue-100 text-sm font-medium uppercase tracking-wider">Opening Balance</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format($openingBalance, 0) }}</p>
                <p class="text-blue-200 text-sm mt-1">Start of day</p>
            </div>

            <!-- Today's Expenses -->
            <div class="bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-rose-100 text-sm font-medium uppercase tracking-wider">Day's Expenses</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format($todayExpenses, 0) }}</p>
                <p class="text-rose-200 text-sm mt-1">{{ $expenses->count() }}
                    expense{{ $expenses->count() !== 1 ? 's' : '' }}</p>
            </div>

            <!-- Closing Balance -->
            <div
                class="bg-gradient-to-br {{ $closingBalanceValue !== null ? 'from-emerald-500 to-green-600' : 'from-gray-400 to-gray-500' }} rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p
                        class="{{ $closingBalanceValue !== null ? 'text-emerald-100' : 'text-gray-200' }} text-sm font-medium uppercase tracking-wider">
                        Closing Balance</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                </div>
                @if ($closingBalanceValue !== null)
                    <p class="text-2xl font-bold text-white break-all">₦{{ number_format($closingBalanceValue, 0) }}</p>
                    <p class="text-emerald-200 text-sm mt-1">End of day</p>
                @else
                    <p class="text-2xl font-bold text-white">Not set</p>
                    <p class="text-gray-200 text-sm mt-1">Enter below</p>
                @endif
            </div>

            <!-- Profit -->
            <div
                class="bg-gradient-to-br {{ $profit !== null && $profit >= 0 ? 'from-teal-500 to-cyan-600' : ($profit !== null ? 'from-red-600 to-rose-700' : 'from-gray-400 to-gray-500') }} rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p
                        class="{{ $profit !== null ? ($profit >= 0 ? 'text-teal-100' : 'text-red-100') : 'text-gray-200' }} text-sm font-medium uppercase tracking-wider">
                        Profit</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                </div>
                @if ($profit !== null)
                    <p class="text-2xl font-bold text-white break-all">₦{{ number_format(abs($profit), 0) }}</p>
                    <p class="{{ $profit >= 0 ? 'text-teal-200' : 'text-red-200' }} text-sm mt-1">
                        Closing - Opening - Expenses
                    </p>
                @else
                    <p class="text-2xl font-bold text-white">—</p>
                    <p class="text-gray-200 text-sm mt-1">Set closing balance first</p>
                @endif
            </div>
        </div>

        <!-- Monthly Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
            <div class="bg-gradient-to-br from-purple-500 to-violet-600 rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-purple-100 text-sm font-medium uppercase tracking-wider">Month's Expenses</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format($monthExpenses, 0) }}</p>
                <p class="text-purple-200 text-sm mt-1">Total this month</p>
            </div>

            <div
                class="bg-gradient-to-br {{ $monthProfit >= 0 ? 'from-green-500 to-emerald-600' : 'from-red-600 to-rose-700' }} rounded-2xl shadow-lg p-5">
                <div class="flex items-center justify-between mb-3">
                    <p
                        class="{{ $monthProfit >= 0 ? 'text-green-100' : 'text-red-100' }} text-sm font-medium uppercase tracking-wider">
                        Month's Profit</p>
                    <div class="bg-white/20 rounded-xl p-2">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-bold text-white break-all">₦{{ number_format(abs($monthProfit), 0) }}</p>
                <p class="{{ $monthProfit >= 0 ? 'text-green-200' : 'text-red-200' }} text-sm mt-1">Net profit this
                    month</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Closing Balance Form -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <h2 class="text-xl font-bold text-gray-900">Enter Closing Balance</h2>
                    <p class="text-sm text-gray-500 mt-1">This becomes tomorrow's opening balance</p>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="closingBalanceInput" class="block text-sm font-medium text-gray-700 mb-1">Closing
                            Balance (₦)</label>
                        <input type="number" wire:model="closingBalance" id="closingBalanceInput" step="0.01"
                            min="0"
                            class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500 text-lg"
                            placeholder="Enter closing balance...">
                        @error('closingBalance')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div>
                        <label for="balanceNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes
                            (optional)</label>
                        <textarea wire:model="balanceNotes" id="balanceNotes" rows="2"
                            class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500"
                            placeholder="Any notes about today..."></textarea>
                        @error('balanceNotes')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <button wire:click="saveClosingBalance" wire:loading.attr="disabled"
                        class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-brand-500 to-brand-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all">
                        <svg wire:loading wire:target="saveClosingBalance"
                            class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Save Closing Balance
                    </button>
                </div>
            </div>

            <!-- Expenses Section -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Expenses</h2>
                            <p class="text-sm text-gray-500 mt-1">Record daily expenses</p>
                        </div>
                        <button wire:click="createExpense"
                            class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-sm font-medium rounded-lg hover:shadow-md transition-all">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Add Expense
                        </button>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    @forelse($expenses as $expense)
                        <div wire:key="expense-{{ $expense->id }}"
                            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $categories[$expense->category] ?? ucfirst($expense->category) }}</p>
                                @if ($expense->description)
                                    <p class="text-xs text-gray-500 truncate">{{ $expense->description }}</p>
                                @endif
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $expense->payment_method === 'CASH' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }} mt-1">
                                    {{ $expense->payment_method }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 ml-4">
                                <span
                                    class="text-sm font-bold text-gray-900">₦{{ number_format($expense->amount, 0) }}</span>
                                <div class="flex items-center gap-1">
                                    <button wire:click="editExpense({{ $expense->id }})"
                                        class="text-gray-400 hover:text-brand-500 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDeleteExpense({{ $expense->id }})"
                                        class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z">
                                </path>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No expenses recorded for this day</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Recent Balances History -->
        <div class="mt-8 bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-xl font-bold text-gray-900">Balance History</h2>
                <p class="text-sm text-gray-500 mt-1">Recent daily records</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Date</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Opening</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Closing</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Expenses</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Profit</th>
                            <th
                                class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Notes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentBalances as $balance)
                            @php
                                $dayExpenses = \App\Models\Expense::where('location_id', $balance->location_id)
                                    ->whereDate('expense_date', $balance->balance_date)
                                    ->sum('amount');
                            @endphp
                            <tr wire:key="balance-{{ $balance->id }}" class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $balance->balance_date->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                    ₦{{ number_format($balance->opening_balance, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                    ₦{{ number_format($balance->closing_balance, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                    ₦{{ number_format($dayExpenses, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-sm font-bold {{ $balance->profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $balance->profit >= 0 ? '' : '-' }}₦{{ number_format(abs($balance->profit), 0) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 max-w-xs truncate">
                                    {{ $balance->notes ?? '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No balance records yet. Start by entering today's closing balance.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Expense Modal -->
    @if ($showExpenseModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    wire:click="$set('showExpenseModal', false)"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        {{ $editExpenseMode ? 'Edit Expense' : 'Add Expense' }}
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select wire:model.live="expenseCategory"
                                class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select category...</option>
                                @foreach ($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('expenseCategory')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        @if ($expenseCategory === 'other')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Specify Expense *</label>
                                <input wire:model="expenseDescription" type="text"
                                    placeholder="Describe the expense..."
                                    class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500">
                                @error('expenseDescription')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₦)</label>
                            <input type="number" wire:model="expenseAmount" step="0.01" min="0.01"
                                class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500"
                                placeholder="0.00">
                            @error('expenseAmount')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select wire:model="expensePaymentMethod"
                                class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500">
                                <option value="CASH">Cash</option>
                                <option value="TRANSFER">Transfer</option>
                                <option value="CARD">Card</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" wire:model="expenseDate"
                                class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500">
                            @error('expenseDate')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        @if ($expenseCategory !== 'other')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description
                                    (optional)</label>
                                <textarea wire:model="expenseDescription" rows="2"
                                    class="w-full rounded-lg border-gray-300 focus:ring-brand-500 focus:border-brand-500"
                                    placeholder="Brief description..."></textarea>
                            </div>
                        @endif
                        <div class="flex gap-3 pt-2">
                            <button wire:click="$set('showExpenseModal', false)"
                                class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                                Cancel
                            </button>
                            <button wire:click="saveExpense" wire:loading.attr="disabled"
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-brand-500 to-brand-600 text-white rounded-lg font-medium hover:shadow-md transition-all">
                                {{ $editExpenseMode ? 'Update' : 'Save' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity"
                    wire:click="$set('showDeleteModal', false)"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 z-10 text-center">
                    <div class="bg-red-100 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Expense</h3>
                    <p class="text-sm text-gray-500 mb-6">Are you sure? This action cannot be undone.</p>
                    <div class="flex gap-3">
                        <button wire:click="$set('showDeleteModal', false)"
                            class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button wire:click="deleteExpense"
                            class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
