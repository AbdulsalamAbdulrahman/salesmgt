<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttendance extends Model
{
    protected $table = 'user_attendance';

    protected $fillable = [
        'user_id',
        'login_at',
        'logout_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get duration in hours
     */
    public function getDurationAttribute()
    {
        if (!$this->logout_at) {
            return null;
        }

        return $this->login_at->diffInMinutes($this->logout_at);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute()
    {
        $minutes = $this->duration;
        
        if ($minutes === null) {
            return 'Active';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$mins}m";
        }

        return "{$mins}m";
    }
}
