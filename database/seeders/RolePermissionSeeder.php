<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'view-dashboard',
            'manage-products',
            'manage-inventory',
            'create-sales',
            'view-sales',
            'view-reports',
            'manage-users',
            'manage-locations',
            'manage-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Salesperson role
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson']);
        $salespersonRole->givePermissionTo([
            'view-dashboard',
            'create-sales',
            'view-sales',
        ]);

        // Create default location
        $location = Location::firstOrCreate(
            ['name' => 'Main Store'],
            [
                'address' => '123 Main Street',
                'phone' => '+1234567890',
                'is_active' => true,
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@salesmgt.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'location_id' => $location->id,
                'can_manage_inventory' => true,
                'is_active' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create sample salesperson
        $salesperson = User::firstOrCreate(
            ['email' => 'salesperson@salesmgt.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'role' => 'salesperson',
                'location_id' => $location->id,
                'can_manage_inventory' => false,
                'is_active' => true,
            ]
        );
        $salesperson->assignRole('salesperson');

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Admin: admin@salesmgt.com / password');
        $this->command->info('Salesperson: salesperson@salesmgt.com / password');
    }
}
