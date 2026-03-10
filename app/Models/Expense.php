<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'location_id',
        'category',
        'amount',
        'payment_method',
        'description',
        'receipt_image',
        'expense_date',
        'offline_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    const CATEGORIES = [
        'utilities' => 'Utilities (Electricity, Water, etc.)',
        'rent' => 'Rent',
        'supplies' => 'Office Supplies',
        'maintenance' => 'Maintenance & Repairs',
        'transport' => 'Transportation',
        'salary' => 'Salary & Wages',
        'marketing' => 'Marketing & Advertising',
        'inventory' => 'Inventory Purchase',
        'equipment' => 'Equipment',
        'delivery' => 'Product Delivery Fee',
        'repairs' => 'Repairs',
        'miscellaneous' => 'Miscellaneous',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['category', 'amount', 'payment_method', 'description', 'expense_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the user who created this expense
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location for this expense
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by category
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
