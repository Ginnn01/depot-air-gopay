<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->string('order_id')->unique();               // order_id yang dikirim ke Midtrans
            $table->string('midtrans_transaction_id')->nullable();
            $table->unsignedInteger('gross_amount');
            $table->text('qr_string')->nullable();              // isi QR untuk ditampilkan
            $table->string('qr_url')->nullable();               // link gambar QR dari Midtrans
            $table->string('transaction_status')->default('pending'); // status dari Midtrans
            $table->timestamp('expires_at')->nullable();
            $table->json('raw_notification')->nullable();       // isi webhook terakhir
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
