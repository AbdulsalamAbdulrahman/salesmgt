<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Shift extends Model
{
    use LogsActivity;

    protected $fillable = [
        'attendant_id',
        'cashier_id',
        'location_id',
        'started_at',
        'ended_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['attendant_id', 'cashier_id', 'location_id', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the attendant for this shift
     */
    public function attendant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendant_id');
    }

    /**
     * Get the cashier who created this shift
     */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the location for this shift
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get sales during this shift
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Scope for active shifts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for shifts at a location
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Get total sales during this shift
     */
    public function getTotalSalesAttribute()
    {
        return $this->sales()->sum('total');
    }

    /**
     * End the shift
     */
    public function endShift()
    {
        $this->update([
            'ended_at' => now(),
            'is_active' => false,
        ]);
    }
}
