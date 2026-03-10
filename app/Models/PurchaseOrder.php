<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PurchaseOrder extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'order_number',
        'location_id',
        'requested_by',
        'approved_by',
        'sent_by',
        'received_by',
        'status',
        'notes',
        'rejection_reason',
        'approved_at',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_SENT = 'sent';

    const STATUS_ORDERED = 'ordered';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_CANCELLED = 'cancelled';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'approved_by', 'received_by', 'delivered_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'PO-'.date('Ymd').'-'.str_pad(static::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the location for this order
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user who requested this order
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the admin who approved this order
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the supplier who received/delivered this order
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Get the supplier who sent this order
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Get the items in this order
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for approved orders
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope for orders awaiting delivery
     */
    public function scopeAwaitingDelivery($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_ORDERED]);
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'blue',
            self::STATUS_REJECTED => 'red',
            self::STATUS_SENT => 'purple',
            self::STATUS_ORDERED => 'indigo',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items()->sum('requested_quantity');
    }

    /**
     * Approve the order
     */
    public function approve($userId)
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the order
     */
    public function reject($userId, $reason = null)
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark as sent by supplier
     */
    public function markAsSent($userId)
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_by' => $userId,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered and update stock
     */
    public function markAsDelivered($userId)
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'received_by' => $userId,
            'delivered_at' => now(),
        ]);

        // Update inventory stock for each item
        $this->loadMissing('items.product');
        foreach ($this->items as $item) {
            $deliveredQty = $item->delivered_quantity ?? $item->approved_quantity ?? $item->requested_quantity;

            // Multiply by qty_per_unit for pack-based products (e.g. a pack of 12 adds 12 to stock)
            $qtyPerUnit = $item->product->qty_per_unit ?? 1;
            if ($qtyPerUnit > 1) {
                $deliveredQty *= $qtyPerUnit;
            }

            $stock = InventoryStock::firstOrCreate(
                [
                    'product_id' => $item->product_id,
                    'location_id' => $this->location_id,
                ],
                ['quantity' => 0]
            );

            $quantityBefore = $stock->quantity;
            $stock->increment('quantity', $deliveredQty);

            // Create inventory movement record
            InventoryMovement::create([
                'product_id' => $item->product_id,
                'location_id' => $this->location_id,
                'user_id' => $userId,
                'type' => 'in',
                'quantity' => $deliveredQty,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $stock->quantity,
                'reference' => $this->order_number,
                'notes' => 'Stock received from Purchase Order: '.$this->order_number,
            ]);
        }
    }
}
