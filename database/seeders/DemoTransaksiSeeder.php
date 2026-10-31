<?php

namespace Database\Seeders;

use App\Models\CashSession;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data transaksi contoh 7 hari terakhir, hanya untuk mencoba tampilan dashboard.
 * Jangan dijalankan di depot sungguhan.
 */
class DemoTransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('role', 'kasir')->firstOrFail();
        $produk = Product::where('is_active', true)->get();

        $isiUlang = $produk->firstWhere('name', 'Isi Ulang Galon') ?? $produk->first();

        for ($mundur = 6; $mundur >= 0; $mundur--) {
            $hari = today()->subDays($mundur);
            $buka = $hari->copy()->setTime(7, 30);
            $hariIni = $mundur === 0;

            $sesi = CashSession::create([
                'user_id' => $kasir->id,
                'opened_at' => $buka,
                'opening_cash' => 100000,
                'status' => 'buka',
                'created_at' => $buka,
                'updated_at' => $buka,
            ]);

            $jumlah = $hariIni ? rand(5, 9) : rand(12, 26);

            for ($n = 1; $n <= $jumlah; $n++) {
                $waktu = $buka->copy()->addMinutes(rand(10, 600));
                $metode = rand(1, 100) <= 40 ? 'qris' : 'tunai';

                // Kebanyakan pembeli hanya isi ulang, sebagian tambah galon baru atau antar
                $items = collect([['produk' => $isiUlang, 'qty' => rand(1, 4)]]);
                foreach ($produk->where('id', '!=', $isiUlang->id) as $lain) {
                    if (rand(1, 100) <= 15) {
                        $items->push(['produk' => $lain, 'qty' => 1]);
                    }
                }

                $total = $items->sum(fn ($i) => $i['produk']->price * $i['qty']);
                $uangDiterima = $metode === 'tunai' ? (int) (ceil($total / 10000) * 10000) : null;

                $trx = Transaction::create([
                    'invoice_number' => 'TRX-' . $hari->format('Ymd') . '-' . str_pad($n, 4, '0', STR_PAD_LEFT),
                    'user_id' => $kasir->id,
                    'cash_session_id' => $sesi->id,
                    'total' => $total,
                    'payment_method' => $metode,
                    'status' => 'lunas',
                    'cash_received' => $uangDiterima,
                    'change_amount' => $uangDiterima !== null ? $uangDiterima - $total : null,
                    'paid_at' => $waktu,
                    'created_at' => $waktu,
                    'updated_at' => $waktu,
                ]);

                foreach ($items as $item) {
                    $trx->items()->create([
                        'product_id' => $item['produk']->id,
                        'product_name' => $item['produk']->name,
                        'price' => $item['produk']->price,
                        'quantity' => $item['qty'],
                        'subtotal' => $item['produk']->price * $item['qty'],
                    ]);
                }

                if ($metode === 'qris') {
                    Payment::create([
                        'transaction_id' => $trx->id,
                        'order_id' => $trx->invoice_number,
                        'gross_amount' => $total,
                        'transaction_status' => 'settlement',
                    ]);
                }
            }

            // Hari-hari sebelumnya ditutup, hari ini dibiarkan terbuka
            if (! $hariIni) {
                $sesi->refresh();
                $sesi->close($sesi->opening_cash + $sesi->cashSalesTotal() - (rand(1, 100) <= 30 ? 2000 : 0));
                $tutup = $hari->copy()->setTime(20, 0);
                $sesi->forceFill(['closed_at' => $tutup, 'updated_at' => $tutup])->save();
            }
        }
    }
}
