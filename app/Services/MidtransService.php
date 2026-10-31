<?php

namespace App\Services;

use App\Models\Transaction;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Transaction as MidtransTransaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production');
        Config::$isSanitized = true;
    }

    /** Minta QRIS dinamis ke Midtrans untuk satu transaksi. */
    public function buatQris(Transaction $transaksi, string $orderId): object
    {
        return CoreApi::charge([
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $transaksi->total,
            ],
            'item_details' => $transaksi->items->map(fn ($item) => [
                'id' => (string) $item->product_id,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'name' => mb_substr($item->product_name, 0, 50),
            ])->values()->all(),
            'qris' => ['acquirer' => 'gopay'],
            'custom_expiry' => ['expiry_duration' => 15, 'unit' => 'minute'],
        ]);
    }

    public function cekStatus(string $orderId): object
    {
        return MidtransTransaction::status($orderId);
    }

    public function batalkan(string $orderId): void
    {
        MidtransTransaction::cancel($orderId);
    }

    /** Pastikan notifikasi benar-benar dari Midtrans, bukan palsu. */
    public function tandaTanganValid(array $data): bool
    {
        $seharusnya = hash('sha512',
            ($data['order_id'] ?? '')
            . ($data['status_code'] ?? '')
            . ($data['gross_amount'] ?? '')
            . config('services.midtrans.server_key')
        );

        return hash_equals($seharusnya, (string) ($data['signature_key'] ?? ''));
    }
}