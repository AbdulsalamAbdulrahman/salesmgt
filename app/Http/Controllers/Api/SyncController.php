<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Batch sync multiple pending transactions.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transactions' => 'required|array|min:1',
            'transactions.*.type' => 'required|in:sale,expense',
            'transactions.*.offline_id' => 'required|string|max:50',
            'transactions.*.data' => 'required|array',
        ]);

        $results = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($validated['transactions'] as $transaction) {
            try {
                if ($transaction['type'] === 'sale') {
                    $result = $this->syncSale($transaction['data'], $transaction['offline_id']);
                } else {
                    $result = $this->syncExpense($transaction['data'], $transaction['offline_id']);
                }

                $results[] = [
                    'offline_id' => $transaction['offline_id'],
                    'type' => $transaction['type'],
                    'success' => $result['success'],
                    'message' => $result['message'],
                    'id' => $result['id'] ?? null,
                ];

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'offline_id' => $transaction['offline_id'],
                    'type' => $transaction['type'],
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
                $failCount++;
            }
        }

        return response()->json([
            'success' => $failCount === 0,
            'message' => "Synced {$successCount} transactions" . ($failCount > 0 ? ", {$failCount} failed" : ''),
            'results' => $results,
            'synced' => $successCount,
            'failed' => $failCount,
        ]);
    }

    private function syncSale(array $data, string $offlineId): array
    {
        // Check for duplicate
        $existingSale = Sale::where('offline_id', $offlineId)->first();
        if ($existingSale) {
            return [
                'success' => true,
                'message' => 'Already synced',
                'id' => $existingSale->id,
            ];
        }

        $user = Auth::user();
        $cart = collect($data['cart']);
        $shiftId = null;
        $attendantId = null;

        // Determine shift and attendant
        if ($user->role === 'attendant') {
            $shift = Shift::where('attendant_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$shift) {
                return [
                    'success' => false,
                    'message' => 'No active shift',
                ];
            }

            $shiftId = $shift->id;
            $attendantId = $user->id;
        } elseif (in_array($user->role, ['cashier', 'admin']) && !empty($data['selected_attendant_id'])) {
            $shift = Shift::where('attendant_id', $data['selected_attendant_id'])
                ->where('is_active', true)
                ->first();

            if ($shift) {
                $shiftId = $shift->id;
                $attendantId = $data['selected_attendant_id'];
            }
        }

        $subtotal = $cart->sum(fn($item) => $item['price'] * $item['quantity']);

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'location_id' => $data['location_id'],
                'user_id' => Auth::id(),
                'shift_id' => $shiftId,
                'attendant_id' => $attendantId,
                'payment_method' => $data['payment_method'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
                'offline_id' => $offlineId,
            ]);

            foreach ($cart as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'cost_price' => $item['cost_price'],
                    'total' => $item['price'] * $item['quantity'],
                    'profit' => ($item['price'] - $item['cost_price']) * $item['quantity'],
                ]);

                $stock = InventoryStock::where('product_id', $item['id'])
                    ->where('location_id', $data['location_id'])
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();

            Log::info('Sale synced via batch', [
                'sale_id' => $sale->id,
                'offline_id' => $offlineId,
            ]);

            return [
                'success' => true,
                'message' => 'Sale synced',
                'id' => $sale->id,
            ];

        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function syncExpense(array $data, string $offlineId): array
    {
        // Check for duplicate
        $existingExpense = Expense::where('offline_id', $offlineId)->first();
        if ($existingExpense) {
            return [
                'success' => true,
                'message' => 'Already synced',
                'id' => $existingExpense->id,
            ];
        }

        $expense = Expense::create([
            'user_id' => Auth::id(),
            'location_id' => $data['location_id'] ?? null,
            'category' => $data['category'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'expense_date' => $data['expense_date'],
            'offline_id' => $offlineId,
        ]);

        Log::info('Expense synced via batch', [
            'expense_id' => $expense->id,
            'offline_id' => $offlineId,
        ]);

        return [
            'success' => true,
            'message' => 'Expense synced',
            'id' => $expense->id,
        ];
    }
}
