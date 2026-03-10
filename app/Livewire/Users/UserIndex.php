<?php

namespace App\Livewire\Users;

use App\Models\User;
use App\Models\Location;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

#[Layout('components.layouts.app')]
#[Title('Users')]
class UserIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editMode = false;
    public $userId;
    
    public $name = '';
    public $email = '';
    public $phone = '';
    public $password = '';
    public $role = 'attendant';
    public $location_id = '';
    public $can_manage_inventory = false;
    public $is_active = true;
    public $address = '';
    public $salary = '';
    public $hire_date = '';
    public $emergency_contact = '';
    public $emergency_phone = '';

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email' . ($this->editMode ? ',' . $this->userId : ''),
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,cashier,attendant,supplier',
            'location_id' => 'nullable|exists:locations,id',
            'can_manage_inventory' => 'boolean',
            'is_active' => 'boolean',
            'address' => 'nullable|string|max:500',
            'salary' => 'nullable|numeric|min:0',
            'hire_date' => 'nullable|date',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
        ];

        if (!$this->editMode) {
            $rules['password'] = 'required|string|min:8';
        } else {
            $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['name', 'email', 'phone', 'password', 'role', 'location_id', 'can_manage_inventory', 'is_active', 'userId', 'editMode', 'address', 'salary', 'hire_date', 'emergency_contact', 'emergency_phone']);
        $this->role = 'attendant';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(User $user)
    {
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->password = '';
        $this->role = $user->role;
        $this->location_id = $user->location_id;
        $this->can_manage_inventory = $user->can_manage_inventory;
        $this->is_active = $user->is_active;
        $this->address = $user->address ?? '';
        $this->salary = $user->salary ?? '';
        $this->hire_date = $user->hire_date?->format('Y-m-d') ?? '';
        $this->emergency_contact = $user->emergency_contact ?? '';
        $this->emergency_phone = $user->emergency_phone ?? '';
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
            'role' => $this->role,
            'location_id' => $this->location_id ?: null,
            'can_manage_inventory' => $this->can_manage_inventory,
            'is_active' => $this->is_active,
            'address' => $this->address ?: null,
            'salary' => $this->salary ?: null,
            'hire_date' => $this->hire_date ?: null,
            'emergency_contact' => $this->emergency_contact ?: null,
            'emergency_phone' => $this->emergency_phone ?: null,
        ];

        if ($this->editMode) {
            $user = User::find($this->userId);
            if ($this->password) {
                $data['password'] = Hash::make($this->password);
            }
            $user->update($data);
            session()->flash('message', 'User updated successfully.');
        } else {
            $data['password'] = Hash::make($this->password);
            User::create($data);
            session()->flash('message', 'User created successfully.');
        }

        $this->showModal = false;
        $this->reset(['name', 'email', 'phone', 'password', 'role', 'location_id', 'can_manage_inventory', 'is_active', 'userId', 'editMode', 'address', 'salary', 'hire_date', 'emergency_contact', 'emergency_phone']);
    }

    public function toggleActive(User $user)
    {
        // Don't allow deactivating yourself
        if ($user->id === Auth::id()) {
            session()->flash('error', 'You cannot deactivate your own account.');
            return;
        }

        $user->update(['is_active' => !$user->is_active]);
        session()->flash('message', 'User status updated successfully.');
    }

    public function render()
    {
        $users = User::query()
            ->with('location')
            ->when($this->search, function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            })
            ->orderBy('name')
            ->paginate(10);

        $locations = Location::where('is_active', true)->orderBy('name')->get();

        return view('livewire.users.user-index', [
            'users' => $users,
            'locations' => $locations,
        ]);
    }
}
