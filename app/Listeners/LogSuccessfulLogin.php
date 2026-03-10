<?php

namespace App\Listeners;

use App\Models\UserAttendance;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Only track for cashiers and attendants
        if (!in_array($user->role, ['cashier', 'attendant'])) {
            return;
        }

        // Close any previous unclosed attendance records
        UserAttendance::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->update(['logout_at' => now()]);

        // Create new attendance record
        UserAttendance::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
