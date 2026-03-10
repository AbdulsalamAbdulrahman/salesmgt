<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create App Settings
        AppSetting::setValue('company_name', 'SalesMgt Store', 'string');
        AppSetting::setValue('company_phone', '+234 800 000 0000', 'string');
        AppSetting::setValue('company_address', '123 Main Street, Lagos, Nigeria', 'string');
        AppSetting::setValue('receipt_footer', 'Thank you for your patronage! Visit us again.', 'string');
        AppSetting::setValue('low_stock_alert_enabled', true, 'boolean');

        // Create Locations
        $locations = [
            ['name' => 'Main Store', 'address' => '123 Main Street, Lagos', 'phone' => '+234 800 000 0001'],
            ['name' => 'Branch 1', 'address' => '456 Second Avenue, Ikeja', 'phone' => '+234 800 000 0002'],
        ];

        foreach ($locations as $locationData) {
            Location::firstOrCreate(['name' => $locationData['name']], $locationData);
        }

        // Create Categories
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories'],
            ['name' => 'Groceries', 'description' => 'Food and household items'],
            ['name' => 'Beverages', 'description' => 'Drinks and refreshments'],
            ['name' => 'Clothing', 'description' => 'Apparel and fashion items'],
            ['name' => 'Office Supplies', 'description' => 'Stationery and office equipment'],
        ];

        foreach ($categories as $categoryData) {
            Category::firstOrCreate(['name' => $categoryData['name']], $categoryData);
        }

        // Create Products
        $products = [
            // Electronics
            ['name' => 'USB Cable', 'category' => 'Electronics', 'cost_price' => 500, 'selling_price' => 800, 'unit' => 'piece'],
            ['name' => 'Power Bank 10000mAh', 'category' => 'Electronics', 'cost_price' => 5000, 'selling_price' => 7500, 'unit' => 'piece'],
            ['name' => 'Wireless Mouse', 'category' => 'Electronics', 'cost_price' => 2500, 'selling_price' => 4000, 'unit' => 'piece'],
            ['name' => 'Earphones', 'category' => 'Electronics', 'cost_price' => 1500, 'selling_price' => 2500, 'unit' => 'piece'],
            
            // Beverages
            ['name' => 'Coca-Cola 50cl', 'category' => 'Beverages', 'cost_price' => 150, 'selling_price' => 200, 'unit' => 'bottle'],
            ['name' => 'Bottled Water 75cl', 'category' => 'Beverages', 'cost_price' => 80, 'selling_price' => 120, 'unit' => 'bottle'],
            ['name' => 'Malt Drink', 'category' => 'Beverages', 'cost_price' => 200, 'selling_price' => 300, 'unit' => 'bottle'],
            
            // Groceries
            ['name' => 'Rice 5kg', 'category' => 'Groceries', 'cost_price' => 4500, 'selling_price' => 5500, 'unit' => 'bag'],
            ['name' => 'Cooking Oil 1L', 'category' => 'Groceries', 'cost_price' => 1200, 'selling_price' => 1500, 'unit' => 'bottle'],
            ['name' => 'Sugar 1kg', 'category' => 'Groceries', 'cost_price' => 800, 'selling_price' => 1000, 'unit' => 'pack'],
            
            // Office Supplies
            ['name' => 'A4 Paper Ream', 'category' => 'Office Supplies', 'cost_price' => 2000, 'selling_price' => 2800, 'unit' => 'ream'],
            ['name' => 'Ball Pen (Pack of 10)', 'category' => 'Office Supplies', 'cost_price' => 300, 'selling_price' => 500, 'unit' => 'pack'],
            ['name' => 'Stapler', 'category' => 'Office Supplies', 'cost_price' => 800, 'selling_price' => 1200, 'unit' => 'piece'],
        ];

        $mainStore = Location::where('name', 'Main Store')->first();
        $branch1 = Location::where('name', 'Branch 1')->first();

        foreach ($products as $index => $productData) {
            $product = Product::firstOrCreate(
                ['name' => $productData['name']],
                [
                    'sku' => 'SKU-' . strtoupper(substr(md5($productData['name']), 0, 8)),
                    'category' => $productData['category'],
                    'cost_price' => $productData['cost_price'],
                    'selling_price' => $productData['selling_price'],
                    'low_stock_threshold' => 10,
                ]
            );

            // Add inventory at Main Store
            Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $mainStore->id],
                ['quantity' => rand(20, 100)]
            );

            // Add inventory at Branch 1
            Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $branch1->id],
                ['quantity' => rand(10, 50)]
            );
        }
    }
}
