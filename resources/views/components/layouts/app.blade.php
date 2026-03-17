<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name', 'Sales Management') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('logo/logo_2.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo/logo_2.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Prevent Alpine.js flash of unstyled content -->
    <style>[x-cloak] { display: none !important; }</style>

    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100"
      x-data="{ sidebarOpen: false, sidebarCollapsed: true, isDesktop: window.innerWidth >= 1024 }"
      x-init="window.addEventListener('resize', () => { isDesktop = window.innerWidth >= 1024 })">
    
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 bg-gray-900 transform lg:translate-x-0 overflow-hidden"
               :class="{
                   'translate-x-0': sidebarOpen,
                   '-translate-x-full': !sidebarOpen && sidebarOpen !== 'hover'
               }"
               @mouseenter="sidebarCollapsed = false"
               @mouseleave="sidebarCollapsed = true"
               :style="{ width: sidebarCollapsed ? '80px' : '256px', transition: 'width 200ms ease-in-out' }">
            <div class="flex items-center justify-center h-14 bg-gray-800 px-3 flex-shrink-0">
                <img src="{{ asset('logo/logo_2.png') }}" alt="Logo" class="h-10 w-auto">
            </div>
            
            <nav class="mt-4 overflow-y-auto" style="max-height: calc(100vh - 56px);">
                <div class="px-2 space-y-1">
                    <!-- Dashboard -->
                    @if(auth()->user()->role === 'shop_manager')
                    <a href="{{ route('simple-shop.dashboard') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors whitespace-nowrap {{ request()->routeIs('simple-shop.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'POSshop' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">POSshop</span>
                    </a>
                    @elseif(auth()->user()->role !== 'supplier')
                    <a href="{{ route('dashboard') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('dashboard') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Dashboard' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Dashboard</span>
                    </a>
                    @endif

                    <!-- POS / New Sale -->
                    @if(!in_array(auth()->user()->role, ['supplier', 'shop_manager']))
                    <a href="{{ route('pos') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('pos') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Point of Sale' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Point of Sale</span>
                    </a>
                    @endif

                    <!-- Sales -->
                    @if(!in_array(auth()->user()->role, ['supplier', 'shop_manager']))
                    <a href="{{ route('sales.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('sales.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Sales History' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Sales History</span>
                    </a>
                    @endif

                    <!-- Categories (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('categories.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('categories.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Categories' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Categories</span>
                    </a>
                    @endif

                    <!-- Products (Admin only) -->
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('products.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('products.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Products' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Products</span>
                    </a>
                    @endif

                    <!-- Inventory (Admin or users with inventory permission) -->
                    @if(auth()->user()->role === 'admin' || auth()->user()->can_manage_inventory)
                    <a href="{{ route('inventory.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('inventory.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Inventory' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Inventory</span>
                    </a>
                    @endif

                    <!-- Shifts (Admin/Cashier) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                    <a href="{{ route('shifts.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('shifts.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Shifts' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Shifts</span>
                    </a>
                    @endif

                    <!-- Purchase Orders -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier', 'supplier']))
                    <a href="{{ route('purchase-orders.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('purchase-orders.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Purchase Orders' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Purchase Orders</span>
                    </a>
                    @endif

                    <!-- Expenses (Admin/Cashier) -->
                    @if(in_array(auth()->user()->role, ['admin', 'cashier']))
                    <a href="{{ route('expenses.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('expenses.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Expenses' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Expenses</span>
                    </a>
                    @endif

                    @if(auth()->user()->role === 'admin')
                    <!-- Locations (Admin only) -->
                    <a href="{{ route('locations.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('locations.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Locations' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Locations</span>
                    </a>

                    <!-- Reports (Admin only) -->
                    <a href="{{ route('reports.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('reports.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Reports' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Reports</span>
                    </a>

                    <!-- Staff Attendance (Admin only) -->
                    <a href="{{ route('attendance.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('attendance.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Attendance' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Attendance</span>
                    </a>

                    <!-- Users (Admin only) -->
                    <a href="{{ route('users.index') }}" wire:navigate
                       class="flex items-center px-3 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('users.*') ? 'bg-gray-800 text-white' : '' }}"
                       :title="sidebarCollapsed ? 'Users' : ''">
                        <svg class="w-5 h-5 flex-shrink-0" :class="{ 'mr-0': sidebarCollapsed, 'mr-3': !sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span x-show="!sidebarCollapsed" class="text-sm">Users</span>
                    </a>
                    @endif
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-h-screen"
             :style="{ marginLeft: isDesktop ? (sidebarCollapsed ? '80px' : '256px') : '0', transition: 'margin-left 200ms ease-in-out' }">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-4 h-14">
                    <!-- Mobile menu button -->
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>

                    <div class="flex-1 px-4 lg:px-0">
                        <h1 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h1>
                    </div>

                    <!-- User dropdown -->
                    <div class="flex items-center space-x-4">
                        <!-- Network Status Indicator -->
                        <div x-data="networkStatus()" x-init="init()" class="flex items-center">
                            <!-- Offline indicator -->
                            <div x-show="isOffline" x-cloak class="flex items-center gap-2 px-3 py-1.5 bg-amber-100 text-amber-700 rounded-full text-sm font-medium">
                                <svg class="w-4 h-4 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
                                </svg>
                                <span class="hidden sm:inline">Offline</span>
                            </div>
                            
                            <!-- Pending sync indicator -->
                            <button x-show="!isOffline && pendingCount > 0" 
                                    x-cloak
                                    @click="syncNow()" 
                                    class="flex items-center gap-2 px-3 py-1.5 bg-blue-100 text-blue-700 rounded-full text-sm font-medium hover:bg-blue-200 transition-colors"
                                    :disabled="syncInProgress">
                                <svg class="w-4 h-4" :class="{ 'animate-spin': syncInProgress }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                <span class="hidden sm:inline" x-text="syncInProgress ? 'Syncing...' : pendingCount + ' pending'"></span>
                                <span class="sm:hidden" x-text="pendingCount"></span>
                            </button>
                            
                            <!-- Slow connection indicator -->
                            <div x-show="!isOffline && pendingCount === 0 && isSlowConnection" x-cloak class="flex items-center gap-2 px-3 py-1.5 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <span class="hidden sm:inline">Slow</span>
                            </div>
                        </div>
                        
                        <div class="relative" x-data="{ userDropdown: false }">
                            <button @click="userDropdown = !userDropdown" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <div class="w-8 h-8 bg-brand-500 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                </div>
                                <span class="hidden md:block">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="userDropdown" 
                                 x-cloak
                                 @click.away="userDropdown = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 origin-top-right">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                                    <p class="text-xs text-brand-500 capitalize">{{ auth()->user()->role }}</p>
                                </div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
                <!-- Animated Page Content -->
                <div x-data x-init="$el.classList.add('animate-fade-in')">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div x-show="sidebarOpen" 
         x-cloak
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Global Toast Notifications -->
    <x-toast-notifications />

    @livewireScripts
    @stack('scripts')
    
    <!-- Network Status Alpine Component -->
    <script>
        function networkStatus() {
            return {
                isOffline: !navigator.onLine,
                isSlowConnection: false,
                pendingCount: 0,
                syncInProgress: false,
                
                init() {
                    this.updateStatus();
                    
                    window.addEventListener('online', () => {
                        this.isOffline = false;
                        this.updateStatus();
                    });
                    
                    window.addEventListener('offline', () => {
                        this.isOffline = true;
                    });
                    
                    // Check for slow connection
                    if (navigator.connection) {
                        this.checkConnection();
                        navigator.connection.addEventListener('change', () => this.checkConnection());
                    }
                    
                    // Listen for sync events
                    window.addEventListener('offline-sync-complete', () => {
                        this.syncInProgress = false;
                        this.updateStatus();
                    });
                    
                    // Poll for pending count
                    setInterval(() => this.updateStatus(), 5000);
                },
                
                checkConnection() {
                    if (navigator.connection) {
                        const conn = navigator.connection;
                        this.isSlowConnection = conn.effectiveType === 'slow-2g' || conn.effectiveType === '2g';
                    }
                },
                
                async updateStatus() {
                    if (window.offlineQueue) {
                        try {
                            const status = await window.offlineQueue.getStatus();
                            this.pendingCount = status.pendingCount;
                            this.syncInProgress = status.syncInProgress;
                        } catch (e) {
                            // DB not ready yet
                        }
                    }
                },
                
                async syncNow() {
                    if (window.offlineQueue && !this.isOffline && !this.syncInProgress) {
                        this.syncInProgress = true;
                        try {
                            await window.offlineQueue.syncPending();
                            this.updateStatus();
                        } catch (e) {
                            console.error('Sync failed:', e);
                        } finally {
                            this.syncInProgress = false;
                        }
                    }
                }
            };
        }
    </script>
</body>
</html>
