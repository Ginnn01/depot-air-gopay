@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $jam = now()->hour;
    $sapaan = $jam < 11 ? 'Selamat pagi' : ($jam < 15 ? 'Selamat siang' : ($jam < 18 ? 'Selamat sore' : 'Selamat malam'));
    $rupiah = fn ($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
    $persenTunai = $penjualanHariIni > 0 ? round($tunaiHariIni / $penjualanHariIni * 100) : 0;
    $persenQris = $penjualanHariIni > 0 ? 100 - $persenTunai : 0;
    $inisialDari = fn ($nama) => collect(explode(' ', $nama))->filter()->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->take(2)->implode('');
@endphp

@section('content')
    <header class="kepala">
        <div>
            <h1>{{ $sapaan }}, {{ auth()->user()->name }}</h1>
            <p>{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
        <span class="lencana">
            <span class="titik {{ $kasTerbuka->isNotEmpty() ? 'hidup' : '' }}"></span>
            {{ $kasTerbuka->isNotEmpty() ? 'Depot sedang buka' : 'Belum ada kas yang dibuka' }}
        </span>
    </header>

    <div class="atas-dashboard">
        {{-- Penjualan hari ini --}}
        <section class="hari-ini" aria-label="Penjualan hari ini">
            <svg class="hari-ini-ombak" viewBox="0 0 32 32" aria-hidden="true">
                <path d="M16 3 C16 3 6 14.5 6 20.5 A10 10 0 0 0 26 20.5 C26 14.5 16 3 16 3 Z" fill="#FFFFFF"/>
            </svg>

            <span class="label">Penjualan lunas hari ini</span>
            <span class="besar angka">{{ $rupiah($penjualanHariIni) }}</span>
            <span class="kecil">{{ $jumlahTransaksi }} transaksi lunas</span>

            <div class="komposisi">
                <div class="pipa" role="img" aria-label="Tunai {{ $persenTunai }} persen, QRIS {{ $persenQris }} persen">
                    <span class="tunai" style="width: {{ $persenTunai }}%"></span>
                    <span class="qris" style="width: {{ $persenQris }}%"></span>
                </div>
                <div class="legenda">
                    <div><i style="background: var(--air-muda)"></i> Tunai <strong class="angka">{{ $rupiah($tunaiHariIni) }}</strong></div>
                    <div><i style="background: var(--tutup)"></i> QRIS <strong class="angka">{{ $rupiah($qrisHariIni) }}</strong></div>
                </div>
            </div>
        </section>

        {{-- Grafik 7 hari --}}
        <section class="panel" aria-label="Penjualan 7 hari terakhir">
            <div class="panel-kepala">
                <h2>Penjualan 7 hari terakhir</h2>
                <span class="angka">Total {{ $rupiah($totalMingguIni) }}</span>
            </div>

            <div class="grafik">
                @foreach ($grafik as $hari)
                    <div class="batang {{ $hari['hari_ini'] ? 'sekarang' : '' }}" title="{{ $hari['tanggal'] }}: {{ $rupiah($hari['total']) }}">
                        <span class="nilai angka">{{ $hari['total'] > 0 ? $hari['pendek'] : '' }}</span>
                        <div class="tabung">
                            <div class="air" style="height: {{ $hari['total'] > 0 ? max(4, round($hari['total'] / $tertinggi * 100)) : 0 }}%"></div>
                        </div>
                        <span class="hari">{{ $hari['hari_ini'] ? 'Hari ini' : $hari['hari'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="bawah-dashboard">
        <section class="panel">
            <div class="panel-kepala">
                <h2>Kas yang sedang dibuka</h2>
            </div>
            @if ($kasTerbuka->isEmpty())
                <p class="kosong">Belum ada kasir yang membuka kas hari ini. Kas dibuka dari halaman Kasir sebelum melayani pembeli.</p>
            @else
                <ul class="daftar-kas">
                    @foreach ($kasTerbuka as $sesi)
                        <li>
                            <span class="inisial" aria-hidden="true">{{ $inisialDari($sesi->user->name) }}</span>
                            <div class="teks">
                                <div>{{ $sesi->user->name }}</div>
                                <small>Dibuka {{ $sesi->opened_at->translatedFormat('d M, H:i') }}</small>
                            </div>
                            <div class="angka">{{ $rupiah($sesi->opening_cash) }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="panel">
            <div class="panel-kepala">
                <h2>Transaksi terakhir</h2>
            </div>
            @if ($transaksiTerakhir->isEmpty())
                <p class="kosong">Belum ada transaksi. Transaksi dari halaman Kasir akan muncul di sini.</p>
            @else
                <ul class="trx-list">
                    @foreach ($transaksiTerakhir as $trx)
                        <li>
                            <span class="ikon-bayar {{ $trx->payment_method }}" aria-hidden="true">
                                @if ($trx->payment_method === 'qris')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h3M20 18v3"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                                @endif
                            </span>
                            <div class="teks">
                                <div>{{ $trx->invoice_number }}</div>
                                <small>{{ $trx->user->name }}, {{ $trx->created_at->translatedFormat('d M H:i') }}</small>
                            </div>
                            <div class="jumlah angka">
                                {{ $rupiah($trx->total) }}
                                <small><span class="status status-{{ $trx->status }}">{{ ucfirst($trx->status) }}</span></small>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section class="panel">
        <div class="panel-kepala">
            <h2>Produk dan harga</h2>
            <span>{{ $produk->where('is_active', true)->count() }} produk dijual</span>
        </div>
        @if ($produk->isEmpty())
            <p class="kosong">Belum ada produk. Jalankan seeder atau tambahkan produk terlebih dahulu.</p>
        @else
            <div class="produk-chip">
                @foreach ($produk as $item)
                    <div class="chip {{ $item->is_active ? '' : 'mati' }}">
                        <span class="ikon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="1.8" width="6" height="2.6" rx="1"/>
                                <path d="M10 4.4v1.4c0 .9-4 1.5-4 4.2V19a3 3 0 0 0 3 3h6a3 3 0 0 0 3-3v-9c0-2.7-4-3.3-4-4.2V4.4"/>
                                <path d="M6 13h12"/>
                            </svg>
                        </span>
                        <div class="teks">
                            <div>{{ $item->name }}</div>
                            <small class="angka">{{ $rupiah($item->price) }}{{ $item->is_active ? '' : ', tidak dijual' }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endsection
