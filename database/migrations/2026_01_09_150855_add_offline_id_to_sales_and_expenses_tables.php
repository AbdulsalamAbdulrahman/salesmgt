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
            $table->string('offline_id', 50)->nullable()->unique()->after('notes');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('offline_id', 50)->nullable()->unique()->after('receipt_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('offline_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('offline_id');
        });
    }
};
