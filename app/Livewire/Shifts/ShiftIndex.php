<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Shift Management')]
class ShiftIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $showEndShiftModal = false;
    public $selectedShiftId;
    
    // Form fields
    public $attendant_id = '';
    public $notes = '';

    protected function rules()
    {
        return [
            'attendant_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function startShift()
    {
        $this->reset(['attendant_id', 'notes']);
        $this->showModal = true;
    }

    public function saveShift()
    {
        $this->validate();

        $user = Auth::user();
        
        // Check if attendant already has an active shift
        $existingShift = Shift::where('attendant_id', $this->attendant_id)
            ->where('is_active', true)
            ->first();

        if ($existingShift) {
            session()->flash('error', 'This attendant already has an active shift.');
            return;
        }

        Shift::create([
            'attendant_id' => $this->attendant_id,
            'cashier_id' => $user->id,
            'location_id' => $user->location_id,
            'started_at' => now(),
            'is_active' => true,
            'notes' => $this->notes,
        ]);

        $this->showModal = false;
        $this->reset(['attendant_id', 'notes']);
        session()->flash('message', 'Shift started successfully.');
    }

    public function confirmEndShift($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        $this->showEndShiftModal = true;
    }

    public function endShift()
    {
        $shift = Shift::find($this->selectedShiftId);
        
        if ($shift) {
            $shift->endShift();
            session()->flash('message', 'Shift ended successfully. Total sales: ₦' . number_format($shift->total_sales, 2));
        }

        $this->showEndShiftModal = false;
        $this->selectedShiftId = null;
    }

    public function render()
    {
        $user = Auth::user();
        
        $shifts = Shift::query()
            ->with(['attendant', 'cashier', 'location'])
            ->withSum('sales', 'total')
            ->when($user->role !== 'admin', fn($q) => $q->where('location_id', $user->location_id))
            ->when($this->search, function ($q) {
                $q->whereHas('attendant', fn($q2) => $q2->where('name', 'like', "%{$this->search}%"));
            })
            ->orderByDesc('started_at')
            ->paginate(10);

        $attendants = User::where('role', 'attendant')
            ->where('is_active', true)
            ->when($user->role !== 'admin', fn($q) => $q->where('location_id', $user->location_id))
            ->get();

        return view('livewire.shifts.shift-index', [
            'shifts' => $shifts,
            'attendants' => $attendants,
        ]);
    }
}
