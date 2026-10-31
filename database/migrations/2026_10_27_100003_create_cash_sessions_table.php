<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete(); // kasir
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('opening_cash');              // kas awal
            $table->unsignedInteger('expected_cash')->nullable(); // kas awal + penjualan tunai
            $table->unsignedInteger('actual_cash')->nullable();   // uang fisik saat tutup
            $table->integer('difference')->nullable();            // actual - expected (bisa minus)
            $table->text('notes')->nullable();
            $table->enum('status', ['buka', 'tutup'])->default('buka');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
