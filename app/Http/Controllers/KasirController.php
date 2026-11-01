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

        // Shift mengikuti shift kasir. Kalau belum diatur (misalnya pemilik), ditentukan dari jam.
        $shift = $request->user()->shift ?? (now()->hour < 15 ? 'pagi' : 'malam');

        CashSession::create([
            'user_id' => $request->user()->id,
            'shift' => $shift,
            'opened_at' => now(),
            'opening_cash' => $data['opening_cash'],
            'status' => 'buka',
        ]);

        return back()->with('sukses', 'Kas shift ' . $shift . ' dibuka. Silakan mulai melayani pembeli.');
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

        // Kasir hanya melihat uang yang ia hitung, selisih hanya untuk pemilik
        return redirect()->route('kasir')->with('ringkasan_tutup', [
            'aktual' => $sesiKas->actual_cash,
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