<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use App\Livewire\Products\ProductIndex;
use App\Livewire\Sales\SaleIndex;
use App\Livewire\Sales\CreateSale;
use App\Livewire\Inventory\InventoryIndex;
use App\Livewire\Reports\ReportIndex;
use App\Livewire\Reports\AttendanceIndex;
use App\Livewire\Locations\LocationIndex;
use App\Livewire\Categories\CategoryIndex;
use App\Livewire\Users\UserIndex;
use App\Livewire\Shifts\ShiftIndex;
use App\Livewire\PurchaseOrders\PurchaseOrderIndex;
use App\Livewire\Expenses\ExpenseIndex;
use Illuminate\Support\Facades\Auth;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', Login::class)->name('login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    
    // POS (Point of Sale) - New Sale
    Route::get('/pos', CreateSale::class)->name('pos');
    
    // Products
    Route::get('/products', ProductIndex::class)->name('products.index');
    
    // Sales
    Route::get('/sales', SaleIndex::class)->name('sales.index');
    Route::get('/sales/create', CreateSale::class)->name('sales.create');
    
    // Inventory
    Route::get('/inventory', InventoryIndex::class)->name('inventory.index');
    
    // Locations
    Route::get('/locations', LocationIndex::class)->name('locations.index');
    
    // Categories
    Route::get('/categories', CategoryIndex::class)->name('categories.index');
    
    // Users (Admin only)
    Route::get('/users', UserIndex::class)->name('users.index');
    
    // Shifts (Cashier/Admin)
    Route::get('/shifts', ShiftIndex::class)->name('shifts.index');
    
    // Purchase Orders
    Route::get('/purchase-orders', PurchaseOrderIndex::class)->name('purchase-orders.index');
    
    // Expenses (Admin/Cashier)
    Route::get('/expenses', ExpenseIndex::class)->name('expenses.index');
    
    // Reports
    Route::get('/reports', ReportIndex::class)->name('reports.index');
    
    // Staff Attendance (Admin only)
    Route::get('/attendance', AttendanceIndex::class)->name('attendance.index');
    
    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
