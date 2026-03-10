<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'location_id',
        'is_active',
        'can_manage_inventory',
        'address',
        'salary',
        'hire_date',
        'emergency_contact',
        'emergency_phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'can_manage_inventory' => 'boolean',
            'salary' => 'decimal:2',
            'hire_date' => 'date',
        ];
    }

    /**
     * Activity log options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone', 'is_active', 'can_manage_inventory'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the sales made by this user.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the location assigned to this user.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the inventory movements made by this user.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * Get shifts where this user is the attendant
     */
    public function attendantShifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'attendant_id');
    }

    /**
     * Get shifts created by this user (as cashier)
     */
    public function cashierShifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'cashier_id');
    }

    /**
     * Get purchase orders requested by this user
     */
    public function purchaseOrdersRequested(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class, 'requested_by');
    }

    /**
     * Get the current active shift for this attendant
     */
    public function activeShift()
    {
        return $this->attendantShifts()->where('is_active', true)->first();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is cashier
     */
    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    /**
     * Check if user is attendant
     */
    public function isAttendant(): bool
    {
        return $this->role === 'attendant';
    }

    /**
     * Check if user is supplier
     */
    public function isSupplier(): bool
    {
        return $this->role === 'supplier';
    }
}
