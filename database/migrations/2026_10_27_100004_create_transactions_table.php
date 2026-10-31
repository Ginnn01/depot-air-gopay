<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();  // contoh: TRX-20261027-0001
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total');
            $table->enum('payment_method', ['tunai', 'qris']);
            $table->enum('status', ['pending', 'lunas', 'gagal', 'kedaluwarsa', 'batal'])->default('pending');
            $table->unsignedInteger('cash_received')->nullable(); // khusus tunai
            $table->unsignedInteger('change_amount')->nullable(); // kembalian
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
