<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RiwayatKasController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
            'kasir' => ['nullable', 'integer', 'exists:users,id'],
        ], [
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        // Bawaan: 30 hari terakhir
        $dari = Carbon::parse($filter['dari'] ?? today()->subDays(29))->startOfDay();
        $sampai = Carbon::parse($filter['sampai'] ?? today())->endOfDay();
        $kasirDipilih = $filter['kasir'] ?? null;

        $sesi = CashSession::with(['user', 'verifier'])
            ->whereBetween('opened_at', [$dari, $sampai])
            ->when($kasirDipilih, fn ($q) => $q->where('user_id', $kasirDipilih))
            ->withSum(['transactions as tunai_total' => fn ($q) => $q->where('payment_method', 'tunai')->where('status', 'lunas')], 'total')
            ->withSum(['transactions as qris_total' => fn ($q) => $q->where('payment_method', 'qris')->where('status', 'lunas')], 'total')
            ->latest('opened_at')
            ->paginate(15)
            ->withQueryString();

        // Ringkasan untuk kas yang sudah ditutup pada rentang ini
        $ditutup = CashSession::whereBetween('opened_at', [$dari, $sampai])
            ->when($kasirDipilih, fn ($q) => $q->where('user_id', $kasirDipilih))
            ->where('status', 'tutup');

              $ringkasan = [
            'ditutup' => (clone $ditutup)->count(),
            'kurang' => (int) abs((clone $ditutup)->where('difference', '<', 0)->sum('difference')),
            'lebih' => (int) (clone $ditutup)->where('difference', '>', 0)->sum('difference'),
            'kali_kurang' => (clone $ditutup)->where('difference', '<', 0)->count(),
            'kali_lebih' => (clone $ditutup)->where('difference', '>', 0)->count(),
            'kali_pas' => (clone $ditutup)->where('difference', 0)->count(),
            'belum_dicek' => (clone $ditutup)->whereNull('verified_at')->count(),
        ];

        return view('riwayat-kas.index', [
            'sesi' => $sesi,
            'ringkasan' => $ringkasan,
            'daftarKasir' => User::where('role', 'kasir')->orderBy('name')->get(),
            'dari' => $dari,
            'sampai' => $sampai,
            'kasirDipilih' => $kasirDipilih,
        ]);
    }

    public function verifikasi(Request $request, CashSession $cashSession): RedirectResponse
    {
        if ($cashSession->status !== 'tutup') {
            return back()->with('error', 'Kas yang masih dibuka belum bisa dicek.');
        }

        if ($cashSession->verified_at) {
            return back()->with('error', 'Tutup kas ini sudah ditandai dicek sebelumnya.');
        }

        $cashSession->update([
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('sukses', 'Tutup kas ' . $cashSession->user->name . ' tanggal '
            . $cashSession->opened_at->translatedFormat('d M Y') . ' ditandai sudah dicek.');
    }
}