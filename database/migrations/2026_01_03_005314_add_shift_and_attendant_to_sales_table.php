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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('location_id')->constrained('shifts')->nullOnDelete();
            $table->foreignId('attendant_id')->nullable()->after('shift_id')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropForeign(['attendant_id']);
            $table->dropColumn(['shift_id', 'attendant_id']);
        });
    }
};
