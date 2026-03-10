<div>
    <!-- Header with Filters -->
    <div class="mb-6" 
        x-data="{
            filters: {
                startDate: '{{ $startDate }}',
                endDate: '{{ $endDate }}',
                userId: '{{ $userId }}',
                role: '{{ $role }}'
            },
            init() {
                this.$watch('filters.startDate', () => this.applyFilters());
                this.$watch('filters.endDate', () => this.applyFilters());
                this.$watch('filters.userId', () => this.applyFilters());
                this.$watch('filters.role', () => this.applyFilters());
            },
            applyFilters() {
                $wire.applyFilters(this.filters);
            }
        }">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Staff Attendance</h1>
                <p class="text-sm text-gray-500 mt-1">Track login and logout times for staff</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" x-model="filters.startDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" x-model="filters.endDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Staff Member</label>
                    <select x-model="filters.userId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <option value="">All Staff</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                    <select x-model="filters.role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-500 focus:border-transparent">
                        <option value="">All Roles</option>
                        <option value="cashier">Cashier</option>
                        <option value="attendant">Attendant</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-brand-500 to-brand-600 rounded-2xl p-4 text-white">
            <p class="text-brand-100 text-xs font-medium uppercase">Total Logins</p>
            <p class="text-2xl font-bold mt-1">{{ number_format($totalRecords) }}</p>
            <p class="text-brand-200 text-sm">in selected period</p>
        </div>
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-4 text-white">
            <p class="text-blue-100 text-xs font-medium uppercase">Total Hours</p>
            <p class="text-2xl font-bold mt-1">{{ $totalHours }} hrs</p>
            <p class="text-blue-200 text-sm">worked in period</p>
        </div>
        <div class="bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-4 text-white">
            <p class="text-amber-100 text-xs font-medium uppercase">Staff Members</p>
            <p class="text-2xl font-bold mt-1">{{ $staffSummary->count() }}</p>
            <p class="text-amber-200 text-sm">logged in during period</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 text-white">
            <p class="text-green-100 text-xs font-medium uppercase">Active Now</p>
            <p class="text-2xl font-bold mt-1">{{ $activeNow }}</p>
            <p class="text-green-200 text-sm">currently logged in</p>
        </div>
    </div>

    <!-- Staff Summary Cards -->
    @if($staffSummary->isNotEmpty())
    <div class="mb-6">
        <h2 class="text-lg font-bold text-gray-800 mb-3">Staff Summary</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($staffSummary as $summary)
            <div class="bg-white rounded-xl shadow-sm border {{ $summary->is_active ? 'border-green-300 ring-2 ring-green-100' : 'border-gray-100' }} p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="relative">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white font-bold">
                            {{ strtoupper(substr($summary->user->name ?? 'U', 0, 1)) }}
                        </div>
                        @if($summary->is_active)
                        <span class="absolute -bottom-0.5 -right-0.5 flex h-3.5 w-3.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-green-500 border-2 border-white"></span>
                        </span>
                        @endif
                    </div>
                    <div>
                        <p class="font-semibold text-gray-800">{{ $summary->user->name ?? 'Unknown' }}</p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-1.5 py-0.5 rounded-full font-medium 
                                {{ $summary->user->role === 'cashier' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ ucfirst($summary->user->role) }}
                            </span>
                            <span class="text-xs text-gray-500">{{ $summary->user->location->name ?? '' }}</span>
                        </div>
                    </div>
                    @if($summary->is_active)
                    <span class="ml-auto px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Online</span>
                    @endif
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="bg-gray-50 rounded-lg p-2">
                        <p class="text-lg font-bold text-gray-800">{{ $summary->days_present }}</p>
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Days</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2">
                        <p class="text-lg font-bold text-gray-800">{{ $summary->total_sessions }}</p>
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Sessions</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2">
                        <p class="text-lg font-bold text-gray-800">{{ $summary->total_hours }}</p>
                        <p class="text-[10px] text-gray-500 uppercase font-medium">Hours</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400 mt-2">Last login: {{ $summary->last_login->format('M d, h:i A') }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Detailed Attendance Log -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <h2 class="text-base font-bold text-gray-800">Attendance Log</h2>
            <p class="text-xs text-gray-500 mt-0.5">Detailed login/logout records</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Staff</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logout Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attendanceRecords as $record)
                        <tr class="{{ is_null($record->logout_at) ? 'bg-green-50' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center">
                                        <span class="text-brand-600 font-semibold">{{ substr($record->user->name ?? 'U', 0, 1) }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900">{{ $record->user->name ?? 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->ip_address }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $record->user->role === 'cashier' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ ucfirst($record->user->role ?? 'Unknown') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $record->user->location->name ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $record->login_at->format('M d, Y') }}</div>
                                <div class="text-sm text-gray-500">{{ $record->login_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($record->logout_at)
                                    <div class="text-sm text-gray-900">{{ $record->logout_at->format('M d, Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $record->logout_at->format('h:i A') }}</div>
                                @else
                                    <span class="text-sm text-green-600 font-medium">Still Active</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($record->logout_at)
                                    <span class="text-sm font-medium text-gray-900">{{ $record->formatted_duration }}</span>
                                @else
                                    <span class="text-sm text-green-600">
                                        {{ now()->diff($record->login_at)->format('%H:%I') }} (ongoing)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(is_null($record->logout_at))
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        🟢 Online
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Completed
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-gray-500">No attendance records found for the selected filters</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $attendanceRecords->links() }}
        </div>
    </div>
</div>
