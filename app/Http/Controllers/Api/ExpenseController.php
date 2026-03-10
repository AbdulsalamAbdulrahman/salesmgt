<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    /**
     * Store a new expense (for offline sync support).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|in:CASH,TRANSFER,CARD',
            'description' => 'nullable|string|max:500',
            'expense_date' => 'required|date',
            'location_id' => 'nullable|integer|exists:locations,id',
            'offline_id' => 'nullable|string|max:50',
            'offline_timestamp' => 'nullable|date',
        ]);

        // Check for duplicate offline submission
        if (!empty($validated['offline_id'])) {
            $existingExpense = Expense::where('offline_id', $validated['offline_id'])->first();
            if ($existingExpense) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expense already synced',
                    'expense_id' => $existingExpense->id,
                    'duplicate' => true,
                ]);
            }
        }

        try {
            $expense = Expense::create([
                'user_id' => Auth::id(),
                'location_id' => $validated['location_id'] ?? null,
                'category' => $validated['category'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'] ?? 'CASH',
                'description' => $validated['description'] ?? null,
                'expense_date' => $validated['expense_date'],
                'offline_id' => $validated['offline_id'] ?? null,
            ]);

            Log::info('Expense created via API', [
                'expense_id' => $expense->id,
                'offline_id' => $validated['offline_id'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense recorded successfully',
                'expense_id' => $expense->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Expense API error', [
                'error' => $e->getMessage(),
                'offline_id' => $validated['offline_id'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error recording expense: ' . $e->getMessage(),
            ], 500);
        }
    }
}
