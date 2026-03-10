<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update purchase_order_items with null unit_cost using their product's cost_price
        DB::statement('
            UPDATE purchase_order_items 
            SET unit_cost = (
                SELECT cost_price 
                FROM products 
                WHERE products.id = purchase_order_items.product_id
            )
            WHERE unit_cost IS NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this migration
    }
};
