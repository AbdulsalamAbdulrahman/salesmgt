<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'requested_quantity',
        'approved_quantity',
        'delivered_quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    /**
     * Get the purchase order this item belongs to
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the product for this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the quantity to be delivered (approved or requested)
     */
    public function getQuantityToDeliverAttribute(): int
    {
        return $this->approved_quantity ?? $this->requested_quantity;
    }

    /**
     * Get total cost for this item
     */
    public function getTotalCostAttribute()
    {
        if (!$this->unit_cost) {
            return null;
        }
        $qty = $this->delivered_quantity ?? $this->approved_quantity ?? $this->requested_quantity;
        return $this->unit_cost * $qty;
    }
}
