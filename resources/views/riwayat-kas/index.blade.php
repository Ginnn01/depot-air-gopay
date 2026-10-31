@extends('layouts.app')

@section('title', 'Riwayat Kas')

@php
    $rupiah = fn ($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
    $inisialDari = fn ($nama) => collect(explode(' ', $nama))->filter()
        ->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->take(2)->implode('');

    $bersih = $ringkasan['lebih'] - $ringkasan['kurang'];
    $pembagi = max($ringkasan['ditutup'], 1);
    $persen = [
        'pas' => round($ringkasan['kali_pas'] / $pembagi * 100),
        'lebih' => round($ringkasan['kali_lebih'] / $pembagi * 100),
        'kurang' => round($ringkasan['kali_kurang'] / $pembagi * 100),
    ];

    $perTanggal = $sesi->getCollection()->groupBy(fn ($s) => $s->opened_at->toDateString());
    $adaFilter = request()->hasAny(['dari', 'sampai', 'kasir']);
@endphp

@section('content')
    <header class="kepala">
        <div>
            <h1>Riwayat kas</h1>
            <p>Cocokkan uang di laci dengan penjualan tunai setiap kali kasir menutup kas.</p>
        </div>
    </header>

    @if (session('sukses'))
        <div class="pemberitahuan berhasil" role="status">{{ session('sukses') }}</div>
    @endif
    @if (session('error'))
        <div class="pemberitahuan gagal" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="pemberitahuan gagal" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="GET" action="{{ route('riwayat-kas') }}" class="saring">
        <div class="kolom">
            <label for="dari">Dari</label>
            <input type="date" id="dari" name="dari" value="{{ $dari->toDateString() }}">
        </div>
        <div class="kolom">
            <label for="sampai">Sampai</label>
            <input type="date" id="sampai" name="sampai" value="{{ $sampai->toDateString() }}">
        </div>
        <div class="kolom">
            <label for="kasir">Kasir</label>
            <select id="kasir" name="kasir">
                <option value="">Semua kasir</option>
                @foreach ($daftarKasir as $kasir)
                    <option value="{{ $kasir->id }}" @selected($kasirDipilih == $kasir->id)>{{ $kasir->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="tombol">Tampilkan</button>
        @if ($adaFilter)
            <a href="{{ route('riwayat-kas') }}" class="atur-ulang">Atur ulang</a>
        @endif
    </form>

    <section class="rekap-kas" aria-label="Rekap">
        <div class="rekap-utama {{ $bersih < 0 ? 'minus' : '' }}">
            <span class="ket">Selisih bersih, {{ $dari->translatedFormat('d M') }} sampai {{ $sampai->translatedFormat('d M Y') }}</span>
            <span class="besar">{{ $bersih < 0 ? '−' : ($bersih > 0 ? '+' : '') }}{{ $rupiah(abs($bersih)) }}</span>
            <span class="kecil">Kekurangan {{ $rupiah($ringkasan['kurang']) }}, kelebihan {{ $rupiah($ringkasan['lebih']) }}</span>
        </div>

        <div class="rekap-hasil">
            <span class="ket">Hasil {{ $ringkasan['ditutup'] }} kali tutup kas</span>
            <div class="pipa-hasil" role="img"
                 aria-label="Pas {{ $ringkasan['kali_pas'] }} kali, lebih {{ $ringkasan['kali_lebih'] }} kali, kurang {{ $ringkasan['kali_kurang'] }} kali">
                <span class="pas" style="width: {{ $persen['pas'] }}%"></span>
                <span class="lebih" style="width: {{ $persen['lebih'] }}%"></span>
                <span class="kurang" style="width: {{ $persen['kurang'] }}%"></span>
            </div>
            <ul class="legenda-hasil">
                <li><i style="background: #2FB872"></i>Pas <strong>{{ $ringkasan['kali_pas'] }}</strong></li>
                <li><i style="background: var(--tutup)"></i>Lebih <strong>{{ $ringkasan['kali_lebih'] }}</strong></li>
                <li><i style="background: var(--merah)"></i>Kurang <strong>{{ $ringkasan['kali_kurang'] }}</strong></li>
            </ul>
        </div>

        <div class="rekap-cek {{ $ringkasan['belum_dicek'] > 0 ? 'perlu' : '' }}">
            <span class="ket">Belum dicek</span>
            <span class="besar">{{ $ringkasan['belum_dicek'] }}</span>
            <span class="kecil">
                {{ $ringkasan['belum_dicek'] > 0 ? 'Hitung ulang uangnya, lalu tandai dicek.' : 'Semua tutup kas sudah dicek.' }}
            </span>
        </div>
    </section>

    @if ($sesi->isEmpty())
        <section class="panel">
            <p class="kosong">Tidak ada buka/tutup kas pada rentang tanggal ini. Ubah tanggal atau pilih kasir lain.</p>
        </section>
    @else
        @foreach ($perTanggal as $tanggal => $daftar)
            <section class="hari-kas">
                <h2 class="judul-tanggal">{{ \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}</h2>

                <div class="daftar-sesi">
                    @foreach ($daftar as $s)
                        @php
                            $selisih = (int) $s->difference;
                            $hasil = $s->status === 'buka' ? 'buka' : ($selisih === 0 ? 'pas' : ($selisih > 0 ? 'lebih' : 'kurang'));
                        @endphp

                        <article class="sesi {{ $hasil }}">
                            <div class="sesi-kasir">
                                <span class="inisial" aria-hidden="true">{{ $inisialDari($s->user->name) }}</span>
                                <div>
                                    <strong>{{ $s->user->name }}</strong>
                                    <small>
                                        @if ($s->closed_at)
                                            {{ $s->opened_at->format('H:i') }} sampai {{ $s->closed_at->format('H:i') }}
                                        @else
                                            Sejak {{ $s->opened_at->format('H:i') }}
                                        @endif
                                    </small>
                                    <small>QRIS {{ $rupiah($s->qris_total ?? 0) }}</small>
                                </div>
                            </div>

                            <dl class="sesi-angka">
                                <div>
                                    <dt>Kas awal</dt>
                                    <dd>{{ $rupiah($s->opening_cash) }}</dd>
                                </div>
                                <div>
                                    <dt>Penjualan tunai</dt>
                                    <dd>{{ $rupiah($s->tunai_total ?? 0) }}</dd>
                                </div>
                                <div class="tegas">
                                    <dt>Kas seharusnya</dt>
                                    <dd>{{ $s->status === 'tutup' ? $rupiah($s->expected_cash) : '-' }}</dd>
                                </div>
                                <div class="tegas">
                                    <dt>Uang di laci</dt>
                                    <dd>{{ $s->status === 'tutup' ? $rupiah($s->actual_cash) : '-' }}</dd>
                                </div>
                            </dl>

                            <div class="sesi-hasil">
                                <span class="label-hasil {{ $hasil }}">
                                    @if ($hasil === 'buka')
                                        Masih dibuka
                                    @elseif ($hasil === 'pas')
                                        Pas
                                    @elseif ($hasil === 'lebih')
                                        Lebih {{ $rupiah($selisih) }}
                                    @else
                                        Kurang {{ $rupiah(abs($selisih)) }}
                                    @endif
                                </span>

                                @if ($hasil === 'buka')
                                    <small>Menunggu tutup kas</small>
                                @elseif ($s->verified_at)
                                    <span class="dicek">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                        Sudah dicek
                                    </span>
                                    <small>{{ $s->verifier?->name }}, {{ $s->verified_at->translatedFormat('d M H:i') }}</small>
                                @else
                                    <form method="POST" action="{{ route('riwayat-kas.verifikasi', $s) }}"
                                          onsubmit="return confirm('Tandai tutup kas ini sudah dicek? Pastikan Anda sudah menghitung ulang uangnya.')">
                                        @csrf
                                        <button type="submit" class="tombol tombol-kecil">Tandai dicek</button>
                                    </form>
                                @endif
                            </div>

                            @if ($s->notes)
                                <p class="sesi-catatan">Catatan kasir: {{ $s->notes }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if ($sesi->hasPages())
            <nav class="halaman" aria-label="Halaman">
                @if ($sesi->onFirstPage())
                    <span class="mati">Sebelumnya</span>
                @else
                    <a href="{{ $sesi->previousPageUrl() }}">Sebelumnya</a>
                @endif
                <span>Halaman {{ $sesi->currentPage() }} dari {{ $sesi->lastPage() }}</span>
                @if ($sesi->hasMorePages())
                    <a href="{{ $sesi->nextPageUrl() }}">Berikutnya</a>
                @else
                    <span class="mati">Berikutnya</span>
                @endif
            </nav>
        @endif
    @endif
@endsection