<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Dashboard
            'view dashboard',
            'view admin dashboard',
            
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Locations
            'view locations',
            'create locations',
            'edit locations',
            'delete locations',
            
            // Categories
            'view categories',
            'create categories',
            'edit categories',
            'delete categories',
            
            // Products
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Inventory
            'view inventory',
            'manage inventory',
            'view inventory movements',
            
            // Sales
            'view sales',
            'create sales',
            'void sales',
            'view all sales', // See sales from all users
            
            // Reports
            'view reports',
            'view sales reports',
            'view inventory reports',
            'view profit reports',
            
            // Settings
            'view settings',
            'manage settings',
            
            // Audit
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Salesperson role with limited permissions
        $salespersonRole = Role::firstOrCreate(['name' => 'salesperson']);
        $salespersonRole->givePermissionTo([
            'view dashboard',
            'view products',
            'view inventory',
            'view sales',
            'create sales',
            'view sales reports',
        ]);

        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@salesmgt.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
                'can_manage_inventory' => true,
            ]
        );
        $admin->assignRole('admin');

        // Create a sample salesperson
        $salesperson = User::firstOrCreate(
            ['email' => 'sales@salesmgt.com'],
            [
                'name' => 'Sales Person',
                'password' => Hash::make('password'),
                'is_active' => true,
                'can_manage_inventory' => false,
            ]
        );
        $salesperson->assignRole('salesperson');
    }
}
