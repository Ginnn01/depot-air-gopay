<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $lunasHariIni = Transaction::whereDate('created_at', today())->where('status', 'lunas');

        // Penjualan lunas 7 hari terakhir untuk grafik tabung air
        $penjualanPerTanggal = Transaction::where('status', 'lunas')
            ->where('created_at', '>=', today()->subDays(6))
            ->selectRaw('DATE(created_at) as tanggal, SUM(total) as total')
            ->groupBy('tanggal')
            ->pluck('total', 'tanggal');

        $grafik = collect(range(6, 0))->map(function (int $mundur) use ($penjualanPerTanggal) {
            $hari = today()->subDays($mundur);
            $total = (int) ($penjualanPerTanggal[$hari->toDateString()] ?? 0);

            return [
                'hari' => $hari->translatedFormat('D'),
                'tanggal' => $hari->translatedFormat('d M'),
                'total' => $total,
                'pendek' => $this->rupiahPendek($total),
                'hari_ini' => $mundur === 0,
            ];
        });

        $tertinggi = max($grafik->max('total'), 1);

        return view('dashboard.index', [
            'penjualanHariIni' => (clone $lunasHariIni)->sum('total'),
            'jumlahTransaksi' => (clone $lunasHariIni)->count(),
            'tunaiHariIni' => (clone $lunasHariIni)->where('payment_method', 'tunai')->sum('total'),
            'qrisHariIni' => (clone $lunasHariIni)->where('payment_method', 'qris')->sum('total'),
            'grafik' => $grafik,
            'tertinggi' => $tertinggi,
            'totalMingguIni' => $grafik->sum('total'),
            'kasTerbuka' => CashSession::with('user')->where('status', 'buka')->latest('opened_at')->get(),
            'transaksiTerakhir' => Transaction::with('user')->latest()->take(6)->get(),
            'produk' => Product::orderBy('name')->get(),
        ]);
    }

    /** Contoh: 1250000 menjadi "1,3 jt", 85000 menjadi "85 rb" */
    private function rupiahPendek(int $angka): string
    {
        if ($angka >= 1_000_000) {
            return number_format($angka / 1_000_000, 1, ',', '.') . ' jt';
        }

        if ($angka >= 1_000) {
            return round($angka / 1_000) . ' rb';
        }

        return (string) $angka;
    }
}
