<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\InventoryStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Store a new sale (for offline sync support).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cart' => 'required|array|min:1',
            'cart.*.id' => 'required|integer|exists:products,id',
            'cart.*.name' => 'required|string',
            'cart.*.price' => 'required|numeric|min:0',
            'cart.*.cost_price' => 'required|numeric|min:0',
            'cart.*.quantity' => 'required|integer|min:1',
            'payment_method' => 'required|in:CASH,CARD,TRANSFER',
            'location_id' => 'required|integer|exists:locations,id',
            'notes' => 'nullable|string|max:500',
            'selected_attendant_id' => 'nullable|integer|exists:users,id',
            'offline_id' => 'nullable|string|max:50', // For deduplication
            'offline_timestamp' => 'nullable|date',
            'sale_date' => 'nullable|date|before_or_equal:today',
        ]);

        $user = Auth::user();
        $cart = collect($validated['cart']);

        // Validate backdating - only admin can backdate
        $saleDate = $validated['sale_date'] ?? now()->format('Y-m-d');
        if ($saleDate !== now()->format('Y-m-d') && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Only administrators can record sales for previous dates.',
            ], 403);
        }

        // Check for duplicate offline submission
        if (!empty($validated['offline_id'])) {
            $existingSale = Sale::where('offline_id', $validated['offline_id'])->first();
            if ($existingSale) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sale already synced',
                    'sale_id' => $existingSale->id,
                    'sale_number' => $existingSale->sale_number,
                    'duplicate' => true,
                ]);
            }
        }

        $shiftId = null;
        $attendantId = null;

        // Determine shift and attendant based on user role
        if ($user->role === 'attendant') {
            $shift = Shift::where('attendant_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$shift) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have an active shift.',
                ], 422);
            }

            $shiftId = $shift->id;
            $attendantId = $user->id;
        } elseif (in_array($user->role, ['cashier', 'admin'])) {
            $activeShiftCount = Shift::where('is_active', true)
                ->when($validated['location_id'], fn($q) => $q->where('location_id', $validated['location_id']))
                ->count();

            if ($activeShiftCount > 0 && empty($validated['selected_attendant_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select an attendant for this sale.',
                ], 422);
            }

            if (!empty($validated['selected_attendant_id'])) {
                $shift = Shift::where('attendant_id', $validated['selected_attendant_id'])
                    ->where('is_active', true)
                    ->first();

                if ($shift) {
                    $shiftId = $shift->id;
                    $attendantId = $validated['selected_attendant_id'];
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Selected attendant does not have an active shift.',
                    ], 422);
                }
            }
        }

        $subtotal = $cart->sum(fn($item) => $item['price'] * $item['quantity']);

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'location_id' => $validated['location_id'],
                'user_id' => Auth::id(),
                'shift_id' => $shiftId,
                'attendant_id' => $attendantId,
                'payment_method' => $validated['payment_method'],
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'notes' => $validated['notes'] ?? null,
                'offline_id' => $validated['offline_id'] ?? null,
                'sale_date' => $saleDate,
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

                // Deduct from inventory
                $stock = InventoryStock::where('product_id', $item['id'])
                    ->where('location_id', $validated['location_id'])
                    ->first();

                if ($stock) {
                    $stock->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();

            Log::info('Sale created via API', [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'offline_id' => $validated['offline_id'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sale completed successfully',
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Sale API error', [
                'error' => $e->getMessage(),
                'offline_id' => $validated['offline_id'] ?? null,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing sale: ' . $e->getMessage(),
            ], 500);
        }
    }
}
