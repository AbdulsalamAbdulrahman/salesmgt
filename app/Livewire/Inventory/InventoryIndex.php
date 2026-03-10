<?php

namespace App\Livewire\Inventory;

use App\Models\Location;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Inventory')]
class InventoryIndex extends Component
{
    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $locationId = $user->location_id ?? Location::where('is_active', true)->first()?->id;

        $products = Product::active()
            ->with(['inventoryStocks'])
            ->orderBy('name')
            ->get();

        $locations = Location::where('is_active', true)->get();

        return view('livewire.inventory.inventory-index', [
            'products' => $products,
            'locations' => $locations,
            'defaultLocationId' => $locationId,
            'canManageInventory' => $user->can_manage_inventory || $user->isAdmin(),
        ]);
    }
}
