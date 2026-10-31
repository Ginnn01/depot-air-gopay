<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\MidtransNotificationController;
use App\Http\Controllers\RiwayatKasController;
use App\Http\Controllers\TransaksiController;
use Illuminate\Support\Facades\Route;

// Webhook Midtrans (tanpa login, dilindungi tanda tangan)
Route::post('/midtrans/notification', MidtransNotificationController::class)->name('midtrans.notification');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return auth()->user()->role === 'pemilik'
            ? redirect()->route('dashboard')
            : redirect()->route('kasir');
    })->name('home');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Khusus pemilik
    Route::middleware('role:pemilik')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/riwayat-kas', [RiwayatKasController::class, 'index'])->name('riwayat-kas');
        Route::post('/riwayat-kas/{cashSession}/verifikasi', [RiwayatKasController::class, 'verifikasi'])
            ->name('riwayat-kas.verifikasi');
    });

    // Kasir dan pemilik
    Route::middleware('role:kasir,pemilik')->prefix('kasir')->group(function () {
        Route::get('/', [KasirController::class, 'index'])->name('kasir');
        Route::post('/buka-kas', [KasirController::class, 'bukaKas'])->name('kasir.buka');
        Route::post('/tutup-kas', [KasirController::class, 'tutupKas'])->name('kasir.tutup');
        Route::post('/transaksi-tunai', [TransaksiController::class, 'storeTunai'])->name('kasir.transaksi');
        Route::post('/transaksi-qris', [TransaksiController::class, 'storeQris'])->name('kasir.qris');
                Route::post('/transaksi-qris-manual', [TransaksiController::class, 'storeQrisManual'])->name('kasir.qris.manual');
        Route::get('/transaksi/{transaction}/status', [TransaksiController::class, 'statusQris'])->name('kasir.qris.status');
        Route::post('/transaksi/{transaction}/batal-qris', [TransaksiController::class, 'batalQris'])->name('kasir.qris.batal');
    });
});