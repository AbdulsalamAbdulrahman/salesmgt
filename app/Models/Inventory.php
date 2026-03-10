<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the location
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Check if inventory is low
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->product->low_stock_threshold;
    }

    /**
     * Add stock
     */
    public function addStock(int $quantity, User $user, ?string $notes = null, ?string $reference = null, ?string $referenceType = null): InventoryMovement
    {
        $quantityBefore = $this->quantity;
        $this->increment('quantity', $quantity);

        return InventoryMovement::create([
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'cost_price' => $this->product->cost_price,
            'reference' => $reference,
            'reference_type' => $referenceType,
            'notes' => $notes,
        ]);
    }

    /**
     * Remove stock
     */
    public function removeStock(int $quantity, User $user, ?string $notes = null, ?string $reference = null, ?string $referenceType = null): InventoryMovement
    {
        $quantityBefore = $this->quantity;
        $this->decrement('quantity', $quantity);

        return InventoryMovement::create([
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'user_id' => $user->id,
            'type' => 'out',
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $this->quantity,
            'cost_price' => $this->product->cost_price,
            'reference' => $reference,
            'reference_type' => $referenceType,
            'notes' => $notes,
        ]);
    }

    /**
     * Adjust stock (set to specific quantity)
     */
    public function adjustStock(int $newQuantity, User $user, ?string $notes = null): InventoryMovement
    {
        $quantityBefore = $this->quantity;
        $difference = $newQuantity - $quantityBefore;
        $this->update(['quantity' => $newQuantity]);

        return InventoryMovement::create([
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'user_id' => $user->id,
            'type' => 'adjustment',
            'quantity' => abs($difference),
            'quantity_before' => $quantityBefore,
            'quantity_after' => $newQuantity,
            'cost_price' => $this->product->cost_price,
            'notes' => $notes,
        ]);
    }

    /**
     * Get or create inventory for product at location
     */
    public static function getOrCreate(int $productId, int $locationId): self
    {
        return self::firstOrCreate(
            ['product_id' => $productId, 'location_id' => $locationId],
            ['quantity' => 0]
        );
    }
}
