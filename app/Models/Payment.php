<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Payment extends Model
{
    protected $fillable = [
        'transaction_id', 'order_id', 'midtrans_transaction_id', 'gross_amount',
        'qr_string', 'qr_url', 'transaction_status', 'expires_at', 'raw_notification',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'raw_notification' => 'array',
        'gross_amount' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** Terapkan status dari Midtrans (webhook atau cek status) ke transaksi. */
    public function perbaruiDariMidtrans(array $data): void
    {
        $status = $data['transaction_status'] ?? 'pending';

        $this->update([
            'transaction_status' => $status,
            'midtrans_transaction_id' => $data['transaction_id'] ?? $this->midtrans_transaction_id,
            'raw_notification' => $data,
        ]);

        $transaksi = $this->transaction;

        // Transaksi yang sudah final tidak diubah lagi
        if ($transaksi->status !== 'pending') {
            return;
        }

        if (in_array($status, ['settlement', 'capture'], true)) {
            $dibayar = (int) round((float) ($data['gross_amount'] ?? 0));

            if ($dibayar !== $this->gross_amount) {
                Log::warning('Jumlah bayar QRIS tidak cocok', ['order_id' => $this->order_id, 'dibayar' => $dibayar]);
                return;
            }

            $transaksi->markAsPaid();
        } elseif ($status === 'expire') {
            $transaksi->update(['status' => 'kedaluwarsa']);
        } elseif ($status === 'cancel') {
            $transaksi->update(['status' => 'batal']);
        } elseif (in_array($status, ['deny', 'failure'], true)) {
            $transaksi->update(['status' => 'gagal']);
        }
    }
}