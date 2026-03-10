<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function processMovement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:stock_in,stock_out,adjustment',
            'product_id' => 'required|integer|exists:products,id',
            'location_id' => 'required|integer|exists:locations,id',
            'quantity' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->can_manage_inventory && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $quantity = (int) $validated['quantity'];
        if ($quantity < 1 && $validated['type'] !== 'adjustment') {
            return response()->json(['success' => false, 'message' => 'Quantity must be at least 1.'], 422);
        }

        DB::beginTransaction();
        try {
            $stock = InventoryStock::firstOrCreate(
                ['product_id' => $validated['product_id'], 'location_id' => $validated['location_id']],
                ['quantity' => 0]
            );

            $quantityBefore = $stock->quantity;

            switch ($validated['type']) {
                case 'stock_in':
                    $stock->increment('quantity', $quantity);
                    $movementType = 'IN';
                    break;
                case 'stock_out':
                    if ($stock->quantity < $quantity) {
                        return response()->json(['success' => false, 'message' => 'Insufficient stock. Available: ' . $stock->quantity], 422);
                    }
                    $stock->decrement('quantity', $quantity);
                    $movementType = 'OUT';
                    break;
                case 'adjustment':
                    $stock->quantity = $quantity;
                    $stock->save();
                    $movementType = 'ADJUSTMENT';
                    break;
            }

            InventoryMovement::create([
                'product_id' => $validated['product_id'],
                'location_id' => $validated['location_id'],
                'user_id' => $user->id,
                'type' => $movementType,
                'quantity' => $validated['type'] === 'adjustment' ? abs($stock->fresh()->quantity - $quantityBefore) : $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->fresh()->quantity,
                'notes' => $validated['notes'],
                'reference' => 'INV-' . date('YmdHis'),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully.',
                'new_quantity' => $stock->fresh()->quantity,
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
