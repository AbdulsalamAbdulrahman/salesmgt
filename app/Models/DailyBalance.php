<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyBalance extends Model
{
    protected $fillable = [
        'location_id',
        'user_id',
        'balance_date',
        'opening_balance',
        'closing_balance',
        'profit',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'balance_date' => 'date',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'profit' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('balance_date', $date);
    }
}
