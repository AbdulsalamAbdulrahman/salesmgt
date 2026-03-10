<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Sale extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'sale_number',
        'location_id',
        'user_id',
        'shift_id',
        'attendant_id',
        'payment_method',
        'subtotal',
        'total',
        'notes',
        'offline_id',
        'sale_date',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
        'sale_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sale) {
            if (empty($sale->sale_number)) {
                // Use sale_date if set (for backdating), otherwise use today
                $saleDate = $sale->sale_date ?? now()->format('Y-m-d');
                $dateForNumber = \Carbon\Carbon::parse($saleDate)->format('Ymd');
                
                // Find the highest existing number for this date to prevent duplicates
                $prefix = 'SALE-' . $dateForNumber . '-';
                $latestSale = static::where('sale_number', 'like', $prefix . '%')
                    ->orderBy('sale_number', 'desc')
                    ->first();
                
                if ($latestSale) {
                    // Extract the number from the last sale number (e.g., "0011" from "SALE-20260123-0011")
                    $lastNumber = (int) substr($latestSale->sale_number, -4);
                    $nextNumber = $lastNumber + 1;
                } else {
                    $nextNumber = 1;
                }
                
                $sale->sale_number = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function attendant()
    {
        return $this->belongsTo(User::class, 'attendant_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalProfitAttribute()
    {
        return $this->items->sum(function ($item) {
            return ($item->unit_price - $item->cost_price) * $item->quantity;
        });
    }
}
