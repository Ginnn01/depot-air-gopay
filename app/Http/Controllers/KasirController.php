<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KasirController extends Controller
{
    public function index(Request $request): View
    {
        $sesiKas = $this->sesiAktif($request);
        $penjualanTunai = $sesiKas ? $sesiKas->cashSalesTotal() : 0;

        // Nota tampil setelah bayar tunai (session) atau setelah QRIS lunas (?nota=)
        $idNota = session('transaksi_baru') ?? $request->integer('nota');

        $transaksiBaru = $idNota
            ? Transaction::with('items')
                ->where('user_id', $request->user()->id)
                ->where('status', 'lunas')
                ->find($idNota)
            : null;

        return view('kasir.index', [
            'sesiKas' => $sesiKas,
            'penjualanTunai' => $penjualanTunai,
            'kasSeharusnya' => $sesiKas ? $sesiKas->opening_cash + $penjualanTunai : 0,
            'transaksiBaru' => $transaksiBaru,
            'produk' => Product::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function bukaKas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'opening_cash' => ['required', 'integer', 'min:0'],
        ], [
            'opening_cash.required' => 'Masukkan jumlah uang awal di laci.',
            'opening_cash.integer' => 'Uang awal harus berupa angka tanpa titik.',
        ]);

        if ($this->sesiAktif($request)) {
            return back()->with('error', 'Kas Anda sudah dibuka.');
        }

        CashSession::create([
            'user_id' => $request->user()->id,
            'opened_at' => now(),
            'opening_cash' => $data['opening_cash'],
            'status' => 'buka',
        ]);

        return back()->with('sukses', 'Kas dibuka. Silakan mulai melayani pembeli.');
    }

    public function tutupKas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'actual_cash' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ], [
            'actual_cash.required' => 'Masukkan jumlah uang yang ada di laci.',
            'actual_cash.integer' => 'Uang di laci harus berupa angka tanpa titik.',
        ]);

        $sesiKas = $this->sesiAktif($request);

        if (! $sesiKas) {
            return back()->with('error', 'Tidak ada kas yang sedang dibuka.');
        }

        $sesiKas->close($data['actual_cash'], $data['notes'] ?? null);

        // Data rekap untuk kasir dan pesan WhatsApp ke pemilik.
        // Selisih sengaja tidak dikirim, hanya dilihat pemilik di Riwayat Kas.
        $lunas = $sesiKas->transactions()->where('status', 'lunas');

        return redirect()->route('kasir')->with('ringkasan_tutup', [
            'aktual' => $sesiKas->actual_cash,
            'kasir' => $request->user()->name,
            'tanggal' => $sesiKas->opened_at->translatedFormat('l, d F Y'),
            'dibuka' => $sesiKas->opened_at->format('H:i'),
            'ditutup' => $sesiKas->closed_at->format('H:i'),
            'kas_awal' => $sesiKas->opening_cash,
            'tunai' => (int) (clone $lunas)->where('payment_method', 'tunai')->sum('total'),
            'qris' => (int) (clone $lunas)->where('payment_method', 'qris')->sum('total'),
            'transaksi' => (clone $lunas)->count(),
            'catatan' => $sesiKas->notes,
        ]);
    }

    private function sesiAktif(Request $request): ?CashSession
    {
        return CashSession::where('user_id', $request->user()->id)
            ->where('status', 'buka')
            ->latest('opened_at')
            ->first();
    }
}