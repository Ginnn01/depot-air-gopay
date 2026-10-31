<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TransaksiController extends Controller
{
    public function storeTunai(Request $request): RedirectResponse
    {
        $data = $request->validate($this->aturanPesanan() + [
            'cash_received' => ['required', 'integer', 'min:0'],
        ], $this->pesanValidasi());

        $sesiKas = $this->sesiAktif($request);
        if (! $sesiKas) {
            return back()->with('error', 'Kas belum dibuka. Buka kas terlebih dahulu.');
        }

        $pesanan = $this->siapkanPesanan($data['items']);
        if (! $pesanan) {
            return back()->with('error', 'Ada produk yang sudah tidak dijual. Muat ulang halaman lalu coba lagi.');
        }

        if ($data['cash_received'] < $pesanan['total']) {
            return back()->withErrors(['cash_received' => 'Uang yang diterima kurang dari total belanja.']);
        }

        $transaksi = $this->simpanTransaksi($request, $sesiKas, $pesanan, [
            'payment_method' => 'tunai',
            'status' => 'lunas',
            'cash_received' => $data['cash_received'],
            'change_amount' => $data['cash_received'] - $pesanan['total'],
            'paid_at' => now(),
        ]);

        return redirect()->route('kasir')->with('transaksi_baru', $transaksi->id);
    }

    public function storeQris(Request $request, MidtransService $midtrans): JsonResponse
    {
        $data = $request->validate($this->aturanPesanan(), $this->pesanValidasi());

        $sesiKas = $this->sesiAktif($request);
        if (! $sesiKas) {
            return response()->json(['message' => 'Kas belum dibuka. Buka kas terlebih dahulu.'], 422);
        }

        $pesanan = $this->siapkanPesanan($data['items']);
        if (! $pesanan) {
            return response()->json(['message' => 'Ada produk yang sudah tidak dijual. Muat ulang halaman.'], 422);
        }

        $transaksi = $this->simpanTransaksi($request, $sesiKas, $pesanan, [
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        // Order ID dibuat unik supaya tidak bentrok di Midtrans walau database dikosongkan
        $orderId = $transaksi->invoice_number . '-' . Str::upper(Str::random(4));

        try {
            $hasil = $midtrans->buatQris($transaksi->load('items'), $orderId);
            $qrUrl = collect($hasil->actions ?? [])->firstWhere('name', 'generate-qr-code')?->url;

            if (! $qrUrl) {
                throw new \RuntimeException('Midtrans tidak mengirim gambar QR.');
            }
        } catch (\Throwable $e) {
            Log::error('Gagal membuat QRIS Midtrans', ['invoice' => $transaksi->invoice_number, 'error' => $e->getMessage()]);
            $transaksi->update(['status' => 'gagal']);

            return response()->json([
                'message' => 'Gagal membuat QRIS. Periksa koneksi internet dan kunci Midtrans di .env, lalu coba lagi.',
            ], 502);
        }

        $berakhir = isset($hasil->expiry_time)
            ? Carbon::parse($hasil->expiry_time, 'Asia/Jakarta')
            : now()->addMinutes(15);

        Payment::create([
            'transaction_id' => $transaksi->id,
            'order_id' => $orderId,
            'midtrans_transaction_id' => $hasil->transaction_id ?? null,
            'gross_amount' => $transaksi->total,
            'qr_string' => $hasil->qr_string ?? null,
            'qr_url' => $qrUrl,
            'transaction_status' => $hasil->transaction_status ?? 'pending',
            'expires_at' => $berakhir,
        ]);

        return response()->json([
            'invoice' => $transaksi->invoice_number,
            'total' => $transaksi->total,
            'qr_url' => $qrUrl,
            'expires_at' => $berakhir->toIso8601String(),
            'status_url' => route('kasir.qris.status', $transaksi),
            'batal_url' => route('kasir.qris.batal', $transaksi),
            'nota_url' => route('kasir', ['nota' => $transaksi->id]),
        ]);
    }

    /** Dipanggil halaman kasir setiap beberapa detik selama QR tampil. */
    public function statusQris(Request $request, Transaction $transaction, MidtransService $midtrans): JsonResponse
    {
        $this->pastikanBolehAkses($request, $transaction);
        $pembayaran = $transaction->payment;

        if ($transaction->status === 'pending' && $pembayaran) {
            try {
                $pembayaran->perbaruiDariMidtrans((array) $midtrans->cekStatus($pembayaran->order_id));
            } catch (\Throwable $e) {
                // Midtrans belum bisa dihubungi, dicoba lagi pada pengecekan berikutnya
            }
            $transaction->refresh();
        }

        return response()->json(['status' => $transaction->status]);
    }

    public function batalQris(Request $request, Transaction $transaction, MidtransService $midtrans): JsonResponse
    {
        $this->pastikanBolehAkses($request, $transaction);
        $pembayaran = $transaction->payment;

        if ($transaction->status === 'pending' && $pembayaran) {
            try {
                $midtrans->batalkan($pembayaran->order_id);
            } catch (\Throwable $e) {
                // Tetap lanjut, status dicek ulang di bawah
            }

            try {
                $pembayaran->perbaruiDariMidtrans((array) $midtrans->cekStatus($pembayaran->order_id));
            } catch (\Throwable $e) {
            }

            $transaction->refresh();

            // Kalau ternyata belum dibayar, tandai batal
            if ($transaction->status === 'pending') {
                $transaction->update(['status' => 'batal']);
                $pembayaran->update(['transaction_status' => 'cancel']);
            }
        }

        return response()->json(['status' => $transaction->status]);
    }

        /** QRIS toko (GoPay Merchant): kasir mengonfirmasi setelah melihat notifikasi di HP. */
    public function storeQrisManual(Request $request): RedirectResponse
    {
        $data = $request->validate($this->aturanPesanan() + [
            'referensi' => ['nullable', 'string', 'max:50'],
        ], $this->pesanValidasi());

        $sesiKas = $this->sesiAktif($request);
        if (! $sesiKas) {
            return back()->with('error', 'Kas belum dibuka. Buka kas terlebih dahulu.');
        }

        $pesanan = $this->siapkanPesanan($data['items']);
        if (! $pesanan) {
            return back()->with('error', 'Ada produk yang sudah tidak dijual. Muat ulang halaman lalu coba lagi.');
        }

        $transaksi = $this->simpanTransaksi($request, $sesiKas, $pesanan, [
            'payment_method' => 'qris',
            'status' => 'lunas',
            'paid_at' => now(),
        ]);

        // Catat siapa yang mengonfirmasi, supaya pemilik bisa mencocokkan dengan riwayat di GoPay Merchant
        Payment::create([
            'transaction_id' => $transaksi->id,
            'order_id' => 'MANUAL-' . $transaksi->invoice_number . '-' . Str::upper(Str::random(4)),
            'gross_amount' => $transaksi->total,
            'transaction_status' => 'manual',
            'raw_notification' => [
                'dikonfirmasi_oleh' => $request->user()->name,
                'referensi' => $data['referensi'] ?? null,
                'waktu' => now()->toDateTimeString(),
            ],
        ]);

        return redirect()->route('kasir')->with('transaksi_baru', $transaksi->id);
    }   
    private function aturanPesanan(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    private function pesanValidasi(): array
    {
        return [
            'items.required' => 'Pesanan masih kosong. Pilih produk terlebih dahulu.',
            'cash_received.required' => 'Masukkan jumlah uang yang diterima.',
            'cash_received.integer' => 'Uang diterima harus berupa angka.',
        ];
    }

    private function sesiAktif(Request $request): ?CashSession
    {
        return CashSession::where('user_id', $request->user()->id)
            ->where('status', 'buka')
            ->latest('opened_at')
            ->first();
    }

    /** Gabungkan produk yang sama dan ambil harga dari database, bukan dari browser. */
    private function siapkanPesanan(array $items): ?array
    {
        $jumlah = collect($items)->groupBy('product_id')->map(fn ($baris) => $baris->sum('quantity'));

        $produk = Product::whereIn('id', $jumlah->keys())->where('is_active', true)->get()->keyBy('id');

        if ($produk->count() !== $jumlah->count()) {
            return null;
        }

        $total = $jumlah->reduce(fn ($t, $qty, $id) => $t + $produk[$id]->price * $qty, 0);

        return ['jumlah' => $jumlah, 'produk' => $produk, 'total' => $total];
    }

    private function simpanTransaksi(Request $request, CashSession $sesiKas, array $pesanan, array $atribut): Transaction
    {
        return DB::transaction(function () use ($request, $sesiKas, $pesanan, $atribut) {
            $transaksi = Transaction::create($atribut + [
                'invoice_number' => Transaction::generateInvoiceNumber(),
                'user_id' => $request->user()->id,
                'cash_session_id' => $sesiKas->id,
                'total' => $pesanan['total'],
            ]);

            foreach ($pesanan['jumlah'] as $id => $qty) {
                $transaksi->items()->create([
                    'product_id' => $id,
                    'product_name' => $pesanan['produk'][$id]->name,
                    'price' => $pesanan['produk'][$id]->price,
                    'quantity' => $qty,
                    'subtotal' => $pesanan['produk'][$id]->price * $qty,
                ]);
            }

            return $transaksi;
        });
    }

    private function pastikanBolehAkses(Request $request, Transaction $transaction): void
    {
        abort_unless(
            $transaction->user_id === $request->user()->id || $request->user()->role === 'pemilik',
            403
        );
    }
}