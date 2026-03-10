<div x-data="expenseManager()">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Expenses</h1>
            <p class="text-sm text-gray-500 mt-1">Track and manage business expenses</p>
        </div>
        
        <button @click="openCreateModal()" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Expense
        </button>
    </div>

    <script>
        function expenseManager() {
            return {
                showModal: false,
                isSubmitting: false,
                editMode: false,
                expenseId: null,
                categories: @js($categories),
                locations: @js($locations),
                form: {
                    category: '',
                    amount: '',
                    payment_method: 'CASH',
                    description: '',
                    expense_date: new Date().toISOString().split('T')[0],
                    location_id: @js(auth()->user()->location_id ?? ''),
                },
                errors: {},

                showToast(type, message) {
                    window.dispatchEvent(new CustomEvent('notify', { 
                        detail: { type, message } 
                    }));
                },

                openCreateModal() {
                    this.editMode = false;
                    this.expenseId = null;
                    this.form = {
                        category: '',
                        amount: '',
                        payment_method: 'CASH',
                        description: '',
                        expense_date: new Date().toISOString().split('T')[0],
                        location_id: @js(auth()->user()->location_id ?? ''),
                    };
                    this.errors = {};
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.errors = {};
                },

                validate() {
                    this.errors = {};
                    if (!this.form.category) {
                        this.errors.category = 'Category is required';
                    }
                    if (!this.form.amount || parseFloat(this.form.amount) <= 0) {
                        this.errors.amount = 'Amount must be greater than 0';
                    }
                    if (!this.form.expense_date) {
                        this.errors.expense_date = 'Date is required';
                    }
                    return Object.keys(this.errors).length === 0;
                },

                async saveExpense() {
                    if (!this.validate()) return;
                    if (this.isSubmitting) return;
                    
                    this.isSubmitting = true;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    
                    // Create plain object payload (ensure no proxies for IndexedDB)
                    const payload = JSON.parse(JSON.stringify({
                        category: this.form.category,
                        amount: parseFloat(this.form.amount),
                        payment_method: this.form.payment_method,
                        description: this.form.description || null,
                        expense_date: this.form.expense_date,
                        location_id: this.form.location_id || null,
                        offline_id: window.offlineQueue ? window.offlineQueue.generateOfflineId() : null,
                    }));
                    
                    try {
                        const response = await fetch('/api/expenses', {
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
                            this.showModal = false;
                            this.showToast('success', 'Expense recorded successfully');
                            
                            // Reload page to show updated list
                            setTimeout(() => {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Handle validation errors from server
                            if (result.errors) {
                                this.errors = result.errors;
                            } else {
                                this.showToast('error', result.message || 'Failed to save expense');
                            }
                        }
                    } catch (error) {
                        // Network error - queue for offline sync
                        if (!navigator.onLine && window.offlineQueue) {
                            await window.offlineQueue.addTransaction('expense', payload);
                            this.showModal = false;
                            this.showToast('warning', 'Expense saved offline. Will sync when online.');
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

    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    <!-- Summary Card -->
    <div class="mb-6 bg-gradient-to-r from-brand-500 to-brand-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-brand-100 text-sm">Total Expenses (Filtered Period)</p>
                <p class="text-3xl font-bold mt-1">₦{{ number_format($totalExpenses, 2) }}</p>
                <p class="text-brand-200 text-xs mt-2">
                    {{ \Carbon\Carbon::parse($filterDateFrom)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($filterDateTo)->format('M d, Y') }}
                </p>
            </div>
            <div class="p-4 bg-white/20 rounded-xl">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="mb-6 bg-white rounded-xl shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search expenses..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select wire:model.live="filterCategory" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                <input wire:model.live="filterDateFrom" type="date" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                <input wire:model.live="filterDateTo" type="date" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                    @if(auth()->user()->role === 'admin')
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                    @endif
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($expenses as $expense)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $expense->expense_date->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $expense->expense_date->format('l') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                @switch($expense->category)
                                    @case('utilities') bg-yellow-100 text-yellow-800 @break
                                    @case('rent') bg-purple-100 text-purple-800 @break
                                    @case('supplies') bg-blue-100 text-blue-800 @break
                                    @case('maintenance') bg-orange-100 text-orange-800 @break
                                    @case('transport') bg-green-100 text-green-800 @break
                                    @case('salary') bg-pink-100 text-pink-800 @break
                                    @case('marketing') bg-indigo-100 text-indigo-800 @break
                                    @case('inventory') bg-teal-100 text-teal-800 @break
                                    @case('equipment') bg-cyan-100 text-cyan-800 @break
                                    @default bg-gray-100 text-gray-800
                                @endswitch">
                                {{ $expense->category_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">{{ $expense->description ?: '-' }}</div>
                            @if($expense->location)
                                <div class="text-xs text-gray-500">{{ $expense->location->name }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold 
                                @if($expense->payment_method === 'CASH') bg-green-100 text-green-700
                                @elseif($expense->payment_method === 'TRANSFER') bg-purple-100 text-purple-700
                                @else bg-blue-100 text-blue-700
                                @endif">
                                {{ $expense->payment_method ?? 'CASH' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">₦{{ number_format($expense->amount, 2) }}</div>
                        </td>
                        @if(auth()->user()->role === 'admin')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-brand-500 flex items-center justify-center">
                                            <span class="text-white text-xs font-medium">{{ strtoupper(substr($expense->user->name ?? 'U', 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm text-gray-900">{{ $expense->user->name ?? 'Unknown' }}</div>
                                    </div>
                                </div>
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $expense->id }})" class="text-brand-500 hover:text-brand-700 mr-3">
                                Edit
                            </button>
                            <button wire:click="confirmDelete({{ $expense->id }})" class="text-red-500 hover:text-red-700">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->role === 'admin' ? 7 : 6 }}" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-gray-500">No expenses found for the selected period.</p>
                            <button wire:click="create" class="mt-4 text-brand-500 hover:text-brand-700 font-medium">
                                Record your first expense
                            </button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($expenses->hasPages())
            <div class="px-6 py-4 border-t">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

    <!-- Create Expense Modal (Alpine-powered) -->
    <div x-show="showModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
                 @click="closeModal()"></div>

            <div x-show="showModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-md p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl">
                <div class="absolute top-4 right-4">
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center mb-6">
                    <div class="p-3 rounded-xl bg-brand-100 mr-4">
                        <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Record Expense</h3>
                        <p class="text-sm text-gray-500">Add a new expense entry</p>
                    </div>
                </div>
            
                <form @submit.prevent="saveExpense()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select x-model="form.category" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select Category</option>
                                <template x-for="(label, key) in categories" :key="key">
                                    <option :value="key" x-text="label"></option>
                                </template>
                            </select>
                            <span x-show="errors.category" class="text-red-500 text-sm mt-1 block" x-text="errors.category"></span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₦) *</label>
                            <input x-data="{
                                get displayValue() {
                                    if (!form.amount && form.amount !== 0) return '';
                                    return new Intl.NumberFormat('en-NG').format(form.amount);
                                }
                            }"
                                type="text" inputmode="decimal"
                                :value="displayValue"
                                @input="
                                    let val = $event.target.value.replace(/[^0-9.]/g, '');
                                    let parts = val.split('.');
                                    if (parts.length > 2) val = parts[0] + '.' + parts.slice(1).join('');
                                    form.amount = val ? parseFloat(val) : '';
                                    $event.target.value = val ? new Intl.NumberFormat('en-NG').format(parseFloat(val)) : '';
                                "
                                @blur="$event.target.value = form.amount ? new Intl.NumberFormat('en-NG').format(form.amount) : ''"
                                placeholder="0"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <span x-show="errors.amount" class="text-red-500 text-sm mt-1 block" x-text="errors.amount"></span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="relative cursor-pointer">
                                    <input type="radio" x-model="form.payment_method" value="CASH" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Cash
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" x-model="form.payment_method" value="TRANSFER" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Transfer
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" x-model="form.payment_method" value="CARD" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Card
                                    </div>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                            <input x-model="form.expense_date" type="date" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            <span x-show="errors.expense_date" class="text-red-500 text-sm mt-1 block" x-text="errors.expense_date"></span>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <select x-model="form.location_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                <option value="">No Location</option>
                                <template x-for="location in locations" :key="location.id">
                                    <option :value="location.id" x-text="location.name"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea x-model="form.description" rows="3" placeholder="Optional details about this expense..."
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="button" @click="closeModal()"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white bg-brand-500 hover:bg-brand-600 transition-colors disabled:opacity-50 flex items-center justify-center gap-2">
                            <template x-if="!isSubmitting">
                                <span>Save Expense</span>
                            </template>
                            <template x-if="isSubmitting">
                                <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Expense Modal (Livewire - for editing existing) -->
    <div x-data="{ open: @entangle('showModal') }"
         x-show="open && $wire.editMode"
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
                 class="relative inline-block w-full max-w-md p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl">
                <div class="absolute top-4 right-4">
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="flex items-center mb-6">
                    <div class="p-3 rounded-xl bg-brand-100 mr-4">
                        <svg class="w-6 h-6 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">Edit Expense</h3>
                        <p class="text-sm text-gray-500">Update expense details</p>
                    </div>
                </div>
            
                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select wire:model="category" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                <option value="">Select Category</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₦) *</label>
                            <input wire:model="amount" type="number" step="0.01" min="0" placeholder="0.00"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            @error('amount') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model="payment_method" value="CASH" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-green-500 peer-checked:bg-green-50 peer-checked:text-green-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Cash
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model="payment_method" value="TRANSFER" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Transfer
                                    </div>
                                </label>
                                <label class="relative cursor-pointer">
                                    <input type="radio" wire:model="payment_method" value="CARD" class="peer sr-only">
                                    <div class="px-3 py-2.5 border-2 rounded-xl text-center text-sm font-medium transition-all peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 border-gray-200 text-gray-600 hover:border-gray-300">
                                        Card
                                    </div>
                                </label>
                            </div>
                            @error('payment_method') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                            <input wire:model="expense_date" type="date" 
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                            @error('expense_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <select wire:model="location_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                <option value="">No Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea wire:model="description" rows="3" placeholder="Optional details about this expense..."
                                      class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500"></textarea>
                            @error('description') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white bg-brand-500 hover:bg-brand-600 transition-colors">
                            Update Expense
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
                 @click="$wire.set('showDeleteModal', false)"></div>

            <div x-show="open"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="relative inline-block w-full max-w-sm p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl">
                
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                        <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Delete Expense?</h3>
                    <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this expense? This action cannot be undone.</p>
                    
                    <div class="flex gap-3">
                        <button wire:click="$set('showDeleteModal', false)"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button wire:click="delete"
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white bg-red-500 hover:bg-red-600 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
