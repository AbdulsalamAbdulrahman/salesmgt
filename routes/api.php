<?php

use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are used for offline sync functionality.
| They use web middleware for session-based authentication.
|
*/

Route::middleware(['web', 'auth'])->prefix('api')->group(function () {
    // Sales API
    Route::post('/sales', [SaleController::class, 'store'])->name('api.sales.store');
    
    // Expenses API
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('api.expenses.store');
    
    // Batch sync endpoint for multiple pending transactions
    Route::post('/sync', [SyncController::class, 'sync'])->name('api.sync');

    // Inventory API
    Route::post('/inventory/movement', [InventoryController::class, 'processMovement'])->name('api.inventory.movement');
});
