<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'cashier', 'attendant', 'supplier'])->default('cashier')->after('email');
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete()->after('role');
            $table->boolean('can_manage_inventory')->default(false)->after('location_id');
            $table->boolean('is_active')->default(true)->after('can_manage_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
