<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('shift', ['pagi', 'malam'])->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('shift');
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->enum('shift', ['pagi', 'malam'])->nullable()->after('user_id');
        });

        // Kasir yang sudah ada dimasukkan ke shift pagi
        DB::table('users')->where('role', 'kasir')->whereNull('shift')->update(['shift' => 'pagi']);

        // Riwayat kas lama diberi label shift berdasarkan jam buka kas
        DB::statement("UPDATE cash_sessions SET shift = IF(HOUR(opened_at) < 15, 'pagi', 'malam') WHERE shift IS NULL");
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn('shift');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shift', 'is_active']);
        });
    }
};