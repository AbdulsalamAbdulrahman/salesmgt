<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Locations')]
class LocationIndex extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $showDeleteModal = false;
    public $editMode = false;
    public $locationId;
    public $deleteId = null;
    
    public $name = '';
    public $address = '';
    public $phone = '';
    public $is_active = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'address' => 'nullable|string|max:500',
        'phone' => 'nullable|string|max:20',
        'is_active' => 'boolean',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['name', 'address', 'phone', 'is_active', 'locationId', 'editMode']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(Location $location)
    {
        $this->locationId = $location->id;
        $this->name = $location->name;
        $this->address = $location->address;
        $this->phone = $location->phone;
        $this->is_active = $location->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        if ($this->editMode) {
            Location::find($this->locationId)->update($data);
            session()->flash('message', 'Location updated successfully.');
        } else {
            Location::create($data);
            session()->flash('message', 'Location created successfully.');
        }

        $this->showModal = false;
        $this->reset(['name', 'address', 'phone', 'is_active', 'locationId', 'editMode']);
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        $location = Location::find($this->deleteId);
        
        if (!$location) {
            $this->showDeleteModal = false;
            return;
        }

        // Check if location has any users, stocks, or sales
        if ($location->users()->exists() || $location->inventoryStocks()->exists() || $location->sales()->exists()) {
            session()->flash('error', 'Cannot delete location with associated data.');
            $this->showDeleteModal = false;
            return;
        }
        
        $location->delete();
        $this->showDeleteModal = false;
        $this->deleteId = null;
        session()->flash('message', 'Location deleted successfully.');
    }

    public function render()
    {
        $locations = Location::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount(['users', 'inventoryStocks', 'sales'])
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.locations.location-index', [
            'locations' => $locations,
        ]);
    }
}
