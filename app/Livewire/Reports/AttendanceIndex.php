<?php

namespace App\Livewire\Reports;

use App\Models\UserAttendance;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Staff Attendance')]
class AttendanceIndex extends Component
{
    use WithPagination;

    public $startDate;
    public $endDate;
    public $userId = '';
    public $role = '';

    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    public function applyFilters($filters)
    {
        $this->startDate = $filters['startDate'];
        $this->endDate = $filters['endDate'];
        $this->userId = $filters['userId'];
        $this->role = $filters['role'];
        $this->resetPage();
    }

    public function render()
    {
        $attendanceRecords = UserAttendance::query()
            ->with('user.location')
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->role, function ($q) {
                $q->whereHas('user', fn($sub) => $sub->where('role', $this->role));
            })
            ->whereBetween('login_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
            ->latest('login_at')
            ->paginate(20);

        // Get users for filter dropdown
        $users = User::whereIn('role', ['cashier', 'attendant'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Calculate stats
        $statsQuery = UserAttendance::query()
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->role, function ($q) {
                $q->whereHas('user', fn($sub) => $sub->where('role', $this->role));
            })
            ->whereBetween('login_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);

        $totalRecords = (clone $statsQuery)->count();

        $totalHours = (clone $statsQuery)
            ->whereNotNull('logout_at')
            ->get()
            ->sum(fn($a) => $a->duration / 60);

        $activeNow = UserAttendance::whereNull('logout_at')->count();

        // Staff summary: grouped by user with total sessions and hours
        $staffSummary = UserAttendance::query()
            ->with('user.location')
            ->when($this->userId, fn($q) => $q->where('user_id', $this->userId))
            ->when($this->role, function ($q) {
                $q->whereHas('user', fn($sub) => $sub->where('role', $this->role));
            })
            ->whereBetween('login_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
            ->get()
            ->groupBy('user_id')
            ->map(function ($records) {
                $user = $records->first()->user;
                $totalMinutes = $records->whereNotNull('logout_at')->sum('duration');
                $uniqueDays = $records->map(fn($r) => $r->login_at->toDateString())->unique()->count();
                $isActive = $records->whereNull('logout_at')->isNotEmpty();

                return (object) [
                    'user' => $user,
                    'total_sessions' => $records->count(),
                    'total_hours' => round($totalMinutes / 60, 1),
                    'days_present' => $uniqueDays,
                    'is_active' => $isActive,
                    'last_login' => $records->sortByDesc('login_at')->first()->login_at,
                ];
            })
            ->sortByDesc('total_hours')
            ->values();

        return view('livewire.reports.attendance-index', [
            'attendanceRecords' => $attendanceRecords,
            'users' => $users,
            'totalRecords' => $totalRecords,
            'totalHours' => round($totalHours, 1),
            'activeNow' => $activeNow,
            'staffSummary' => $staffSummary,
        ]);
    }
}
