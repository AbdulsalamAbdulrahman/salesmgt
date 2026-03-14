<div>
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        
        <div class="flex items-center gap-4">
            <div class="relative">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search users..." 
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            
            <button wire:click="create" class="px-4 py-2 bg-brand-500 text-white rounded-lg hover:bg-brand-600 transition-colors">
                Add User
            </button>
        </div>
    </div>

    @if(session('message'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">{{ session('message') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm overflow-hidden overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role / Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hire Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-brand-500 flex items-center justify-center">
                                        <span class="text-white font-medium">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $user->phone ?? '-' }}</div>
                            @if($user->address)
                                <div class="text-xs text-gray-500 max-w-[150px] truncate" title="{{ $user->address }}">{{ $user->address }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @switch($user->role)
                                    @case('admin') bg-purple-100 text-purple-800 @break
                                    @case('cashier') bg-blue-100 text-blue-800 @break
                                    @case('supplier') bg-orange-100 text-orange-800 @break
                                    @case('shop_manager') bg-emerald-100 text-emerald-800 @break
                                    @default bg-brand-100 text-brand-700
                                @endswitch
                            ">
                                {{ ucfirst($user->role) }}
                            </span>
                            <div class="text-xs text-gray-500 mt-1">{{ $user->location?->name ?? 'All Locations' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->salary)
                                <div class="text-sm font-medium text-gray-900">₦{{ number_format($user->salary, 0) }}</div>
                                <div class="text-xs text-gray-500">monthly</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($user->hire_date)
                                <div class="text-sm text-gray-900">{{ $user->hire_date->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $user->hire_date->diffForHumans(['parts' => 1]) }}</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleActive({{ $user->id }})" 
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}"
                                    {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </button>
                            @if($user->role === 'admin' || $user->can_manage_inventory)
                                <div class="text-xs text-green-600 mt-1">Inventory ✓</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $user->id }})" class="text-brand-500 hover:text-brand-700">Edit</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        <div class="px-6 py-4 border-t">
            {{ $users->links() }}
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
                 class="relative inline-block w-full max-w-2xl p-6 my-8 text-left align-middle bg-white shadow-2xl rounded-2xl max-h-[90vh] overflow-y-auto">
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{ $editMode ? 'Edit User' : 'Add New User' }}</h3>
                        <p class="text-sm text-gray-500">{{ $editMode ? 'Update user information' : 'Create a new user account' }}</p>
                    </div>
                </div>
            
                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label>
                                <input wire:model="name" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                @error('name') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <input wire:model="email" type="email" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                @error('email') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input wire:model="phone" type="tel" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="e.g. 08012345678">
                                @error('phone') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Password {{ $editMode ? '(leave blank to keep current)' : '' }} @if(!$editMode)<span class="text-red-500">*</span>@endif
                                </label>
                                <input wire:model="password" type="password" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                @error('password') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea wire:model="address" rows="2" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="Home or office address"></textarea>
                            @error('address') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Role <span class="text-red-500">*</span></label>
                                <select wire:model="role" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                    <option value="attendant">Attendant</option>
                                    <option value="cashier">Cashier</option>
                                    <option value="supplier">Supplier</option>
                                    <option value="shop_manager">Shop Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                                @error('role') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                                <select wire:model="location_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                                    @endforeach
                                </select>
                                @error('location_id') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div x-data="{
                                rawValue: @entangle('salary'),
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Monthly Salary (₦)</label>
                                <input x-ref="input" type="text" inputmode="decimal"
                                    x-model="displayValue"
                                    @input="handleInput($event)"
                                    @blur="handleBlur()"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500" 
                                    placeholder="0">
                                @error('salary') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hire Date</label>
                                <input wire:model="hire_date" type="date" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500">
                                @error('hire_date') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-3">Emergency Contact</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-2">Contact Name</label>
                                    <input wire:model="emergency_contact" type="text" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="e.g. John Doe">
                                    @error('emergency_contact') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 mb-2">Contact Phone</label>
                                    <input wire:model="emergency_phone" type="tel" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-500 focus:border-brand-500" placeholder="e.g. 08012345678">
                                    @error('emergency_phone') <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-6 pt-2">
                            <label class="flex items-center cursor-pointer">
                                <input wire:model="can_manage_inventory" type="checkbox" class="h-5 w-5 text-brand-500 focus:ring-brand-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Can Manage Inventory</span>
                            </label>
                            
                            <label class="flex items-center cursor-pointer">
                                <input wire:model="is_active" type="checkbox" class="h-5 w-5 text-brand-500 focus:ring-brand-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-700 rounded-xl hover:bg-gray-50 font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="flex-1 px-4 py-3 rounded-xl font-medium text-white bg-brand-500 hover:bg-brand-600 transition-colors">
                            {{ $editMode ? 'Update User' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
