<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Location extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'is_active',
        'is_simple_shop',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_simple_shop' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'address', 'phone', 'is_active', 'is_simple_shop'])
            ->logOnlyDirty();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function dailyBalances()
    {
        return $this->hasMany(DailyBalance::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
