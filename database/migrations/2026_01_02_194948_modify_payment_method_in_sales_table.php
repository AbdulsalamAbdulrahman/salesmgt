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
        // First modify the enum to include all values (old and new)
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('CASH', 'POS', 'CARD', 'TRANSFER') DEFAULT 'CASH'");
        
        // Update existing POS values to CARD
        DB::table('sales')->where('payment_method', 'POS')->update(['payment_method' => 'CARD']);
        
        // Now remove POS from enum
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('CASH', 'CARD', 'TRANSFER') DEFAULT 'CASH'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add POS back to enum
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('CASH', 'POS', 'CARD', 'TRANSFER') DEFAULT 'CASH'");
        
        // Convert CARD and TRANSFER back to POS
        DB::table('sales')->whereIn('payment_method', ['CARD', 'TRANSFER'])->update(['payment_method' => 'POS']);
        
        // Remove new values from enum
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('CASH', 'POS') DEFAULT 'CASH'");
    }
};
