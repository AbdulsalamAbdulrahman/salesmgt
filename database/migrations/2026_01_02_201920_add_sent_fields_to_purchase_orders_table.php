<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('sent_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable()->after('approved_at');
        });

        // Update status enum to include 'sent' (skip for SQLite)
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'sent', 'ordered', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['sent_by']);
            $table->dropColumn(['sent_by', 'sent_at']);
        });

        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'ordered', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
    }
};
