<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',
        'sku',
        'description',
        'barcode',
        'category',
        'category_id',
        'cost_price',
        'selling_price',
        'unit_type',
        'qty_per_unit',
        'low_stock_threshold',
        'image',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
        'qty_per_unit' => 'integer',
    ];

    /**
     * Get cost price per individual item (for packs/cartons)
     */
    public function getCostPerItemAttribute()
    {
        if ($this->qty_per_unit > 1) {
            return round($this->cost_price / $this->qty_per_unit, 2);
        }
        return $this->cost_price;
    }

    /**
     * Get expected profit per unit
     */
    public function getExpectedProfitAttribute()
    {
        return $this->selling_price - $this->cost_price;
    }

    /**
     * Get expected profit margin percentage
     */
    public function getProfitMarginAttribute()
    {
        if ($this->selling_price > 0) {
            return round(($this->expected_profit / $this->selling_price) * 100, 1);
        }
        return 0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'sku', 'cost_price', 'selling_price', 'is_active'])
            ->logOnlyDirty();
    }

    public function categoryRelation()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalStockAttribute()
    {
        return $this->inventoryStocks()->sum('quantity');
    }

    public function getStockAtLocation($locationId)
    {
        return $this->inventoryStocks()
            ->where('location_id', $locationId)
            ->first()?->quantity ?? 0;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('inventoryStocks', function ($q) {
            $q->whereRaw('quantity <= low_stock_threshold');
        });
    }
}
