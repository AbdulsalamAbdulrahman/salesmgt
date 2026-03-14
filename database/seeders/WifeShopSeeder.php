<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WifeShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the POSshop location
        $location = Location::firstOrCreate(
            ['name' => 'POSshop'],
            [
                'address' => '',
                'phone' => '',
                'is_active' => true,
                'is_simple_shop' => true,
            ]
        );

        // If existing, ensure it's marked as simple shop
        if (! $location->is_simple_shop) {
            $location->update(['is_simple_shop' => true]);
        }

        // Create the shop manager user
        $user = User::firstOrCreate(
            ['email' => 'posshop@salesmgt.com'],
            [
                'name' => 'POSshop Manager',
                'password' => Hash::make('password'),
                'role' => 'shop_manager',
                'location_id' => $location->id,
                'is_active' => true,
                'can_manage_inventory' => false,
            ]
        );
    }
}
