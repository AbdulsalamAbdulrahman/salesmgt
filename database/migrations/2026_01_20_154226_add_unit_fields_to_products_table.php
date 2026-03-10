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
        Schema::table('products', function (Blueprint $table) {
            $table->string('unit_type')->default('piece')->after('selling_price'); // piece, pack, carton, box, etc.
            $table->integer('qty_per_unit')->default(1)->after('unit_type'); // qty of items in pack/carton
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit_type', 'qty_per_unit']);
        });
    }
};
