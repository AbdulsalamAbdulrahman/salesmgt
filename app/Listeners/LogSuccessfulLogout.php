<?php

namespace App\Listeners;

use App\Models\UserAttendance;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (!$user) {
            return;
        }

        // Only track for cashiers and attendants
        if (!in_array($user->role, ['cashier', 'attendant'])) {
            return;
        }

        // Update the most recent attendance record with logout time
        $attendance = UserAttendance::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($attendance) {
            $attendance->update(['logout_at' => now()]);
        }
    }
}
