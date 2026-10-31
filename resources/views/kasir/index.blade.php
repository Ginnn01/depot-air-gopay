@extends('layouts.app')

@section('title', 'Kasir')

@php
    $rupiah = fn ($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
    $ringkasanTutup = session('ringkasan_tutup');
    $adaGambarQris = file_exists(public_path('images/qris-toko.jpg'));

    // Posisi, ukuran, lama, dan jeda gelembung
    $gelembung = [[18, 8, 3.2, 0], [34, 5, 2.6, .8], [62, 10, 3.6, .4], [76, 6, 2.8, 1.4], [48, 7, 3, 2]];
    // Posisi kiri (%), atas (px), jeda, dan ukuran kilau
    $kilau = [[18, 22, 0, 10], [80, 30, 1.1, 8], [72, 70, .6, 6], [24, 64, 1.7, 7]];
@endphp

@section('content')
    <style>
        /* ==========================================================
           Kartu produk bergaya foto katalog
           ========================================================== */
        .produk-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.4rem 1.2rem; }
        button.produk { background: transparent; border: 0; border-radius: 0; overflow: visible; display: block; padding: 0 0 .3rem; text-align: left; }
        button.produk:hover:not(:disabled) { border-color: transparent; }

        /* Warna aksen biru air untuk semua produk */
        .produk { --aksen: #1E9BC4; --aksen-muda: #CDEFFA; --aksen-dalam: #0B3C5D; }

        .produk-foto {
            position: relative; height: 162px; border-radius: 22px; overflow: hidden; isolation: isolate;
            background: linear-gradient(180deg, #FBFEFF 0%, var(--aksen-muda) 100%);
        }

        /* Lapisan "di dalam air" saat dipilih */
        .produk-foto::before {
            content: ""; position: absolute; inset: 0; z-index: -2; opacity: 0; transition: opacity .4s;
            background: radial-gradient(120% 90% at 50% 0%, color-mix(in srgb, var(--aksen) 70%, white) 0%, var(--aksen) 40%, var(--aksen-dalam) 100%);
        }

        /* Cahaya sorot dari atas */
        .produk-foto .sinar {
            position: absolute; left: 50%; top: -70px; width: 260px; height: 260px; margin-left: -130px; z-index: -1;
            background: repeating-conic-gradient(from 0deg at 50% 50%, rgba(255,255,255,.55) 0deg 6deg, rgba(255,255,255,0) 6deg 18deg);
            -webkit-mask: radial-gradient(circle, #000 0%, transparent 68%);
            mask: radial-gradient(circle, #000 0%, transparent 68%);
            opacity: .7; transition: opacity .4s; animation: putar 40s linear infinite;
        }

        /* Cincin putus-putus dan halo di belakang galon */
        .produk-foto .cincin {
            position: absolute; left: 50%; top: 2px; width: 132px; height: 132px; margin-left: -66px; border-radius: 50%; z-index: -1;
            border: 1.5px dashed color-mix(in srgb, var(--aksen) 35%, transparent);
            transition: border-color .4s; animation: putar 30s linear infinite reverse;
        }
        .produk-foto .halo {
            position: absolute; left: 50%; top: 10px; width: 112px; height: 112px; margin-left: -56px; border-radius: 50%; z-index: -1;
            background: radial-gradient(circle at 50% 40%, #FFFFFF 0%, rgba(255,255,255,.85) 35%, rgba(255,255,255,0) 70%);
            box-shadow: inset 0 0 0 1.5px rgba(255,255,255,.7);
            transition: transform .35s, background .4s;
        }

        /* Pantulan cahaya air (kaustik) saat dipilih */
        .produk-foto .kaustik {
            position: absolute; inset: -20%; z-index: -1; opacity: 0; transition: opacity .4s; mix-blend-mode: screen;
            background:
                radial-gradient(18px 10px at 20% 30%, rgba(255,255,255,.35), transparent 70%),
                radial-gradient(26px 12px at 60% 18%, rgba(255,255,255,.30), transparent 70%),
                radial-gradient(14px 8px at 80% 45%, rgba(255,255,255,.35), transparent 70%),
                radial-gradient(22px 10px at 35% 60%, rgba(255,255,255,.25), transparent 70%),
                radial-gradient(30px 12px at 70% 70%, rgba(255,255,255,.22), transparent 70%);
            background-size: 120px 90px;
            animation: kaustik 7s ease-in-out infinite alternate;
        }

        /* Panggung tempat galon berdiri */
        .produk-foto .panggung {
            position: absolute; left: 50%; top: 98px; width: 96px; height: 18px; margin-left: -48px; border-radius: 50%; z-index: 0;
            background: radial-gradient(ellipse at 50% 35%, #FFFFFF 0%, color-mix(in srgb, var(--aksen) 25%, white) 70%, color-mix(in srgb, var(--aksen) 45%, white) 100%);
            box-shadow: 0 6px 14px -4px color-mix(in srgb, var(--aksen-dalam) 45%, transparent);
            transition: background .4s, box-shadow .4s;
        }

        /* Kilau kecil dan gelembung */
        .kilau { position: absolute; z-index: 0; color: #FFFFFF; opacity: 0; transition: opacity .4s; animation: kedip 2.6s ease-in-out infinite; }
        .kilau svg { width: 100%; height: 100%; display: block; }
        .gelembung {
            position: absolute; bottom: -12px; width: var(--uk, 8px); height: var(--uk, 8px); border-radius: 50%; z-index: 0;
            border: 1.5px solid rgba(255,255,255,.85); background: rgba(255,255,255,.15); opacity: 0;
        }

        /* Galon */
        .produk-foto svg.galon {
            position: absolute; left: 50%; top: 18px; height: 88px; width: auto; z-index: 1;
            transform: translateX(-50%); transition: transform .3s;
        }
        .produk-foto .tutup-galon { fill: var(--tutup); }
        .produk-foto .badan-galon { fill: rgba(255,255,255,.9); stroke: var(--laut-2); stroke-width: 2; transition: stroke .3s; }
        .produk-foto .level-air { transform: translateY(var(--naik, 0px)); transition: transform .5s cubic-bezier(.3, 1.4, .5, 1); }
        .produk-foto .ombak-depan { fill: #1E9BC4; animation: ombak-galon 2.4s linear infinite; animation-delay: var(--tunda, 0s); }
        .produk-foto .ombak-belakang { fill: #5CC3E2; opacity: .6; animation: ombak-galon 3.6s linear infinite reverse; animation-delay: var(--tunda, 0s); }
        .produk-foto .isi-air { animation: naik-turun-air 3s ease-in-out infinite; animation-delay: var(--tunda, 0s); }

        /* Riak saat diklik */
        .riak {
            position: absolute; width: 20px; height: 20px; margin: -10px 0 0 -10px; border-radius: 50%;
            border: 2px solid rgba(255,255,255,.95); pointer-events: none; z-index: 3; animation: riak-air .7s ease-out forwards;
        }

        /* Saat kursor di atas kartu */
        button.produk:hover:not(:disabled) .produk-foto svg.galon { transform: translateX(-50%) translateY(-5px) rotate(-3deg); }
        button.produk:hover:not(:disabled) .produk-foto .halo { transform: scale(1.08); }

        /* Saat dipilih */
        button.produk.dipilih .produk-foto::before,
        button.produk.dipilih .produk-foto .kaustik { opacity: 1; }
        button.produk.dipilih .produk-foto .sinar { opacity: .35; }
        button.produk.dipilih .produk-foto .cincin { border-color: rgba(255,255,255,.35); }
        button.produk.dipilih .produk-foto .halo {
            background: radial-gradient(circle, rgba(255,255,255,.35) 0%, rgba(255,255,255,0) 70%);
            box-shadow: inset 0 0 0 1.5px rgba(255,255,255,.4);
        }
        button.produk.dipilih .produk-foto .panggung {
            background: radial-gradient(ellipse at 50% 35%, rgba(255,255,255,.9), rgba(255,255,255,.25) 75%);
            box-shadow: 0 0 22px 2px rgba(255,255,255,.45);
        }
        button.produk.dipilih .produk-foto .badan-galon { stroke: #FFFFFF; }
        button.produk.dipilih .produk-foto svg.galon { transform: translateX(-50%) translateY(-6px); }
        button.produk.dipilih .kilau { opacity: 1; }
        button.produk.dipilih .gelembung { animation: gelembung-naik var(--lama, 3s) ease-in infinite; animation-delay: var(--jeda, 0s); }

        /* Kartu teks di bawah gambar */
        .produk-kartu {
            position: relative; z-index: 2; margin: -44px 12px 0; padding: .9rem 1rem 1rem;
            background: var(--putih); border-radius: 16px; box-shadow: 0 10px 24px rgba(11,60,93,.12);
            display: grid; gap: .15rem; transition: background .25s, transform .25s;
        }
        button.produk:hover:not(:disabled) .produk-kartu { transform: translateY(-3px); }
        .produk-kategori { font-size: .8rem; font-weight: 500; color: var(--aksen); }
        .produk-nama { font-weight: 600; color: var(--tinta); line-height: 1.3; }
        .produk-harga { margin-top: .35rem; font-weight: 600; color: var(--laut-2); font-variant-numeric: tabular-nums; }
        button.produk.dipilih .produk-kartu { background: var(--aksen); }
        button.produk.dipilih .produk-kategori,
        button.produk.dipilih .produk-nama,
        button.produk.dipilih .produk-harga { color: var(--putih); }

        .produk-jumlah {
            position: absolute; top: 10px; right: 10px; z-index: 3; min-width: 30px; height: 30px; padding: 0 .55rem;
            border-radius: 999px; background: var(--putih); color: var(--aksen-dalam);
            display: grid; place-items: center; font-weight: 700; font-size: .9rem; box-shadow: 0 4px 10px rgba(0,0,0,.15);
        }
        .produk-jumlah[hidden] { display: none; }

        @keyframes putar { to { transform: rotate(360deg); } }
        @keyframes kedip { 0%, 100% { transform: scale(.6); opacity: .3; } 50% { transform: scale(1); opacity: 1; } }
        @keyframes kaustik {
            from { background-position: 0 0, 10px 5px, 0 0, 5px 0, 0 10px; transform: rotate(0deg); }
            to { background-position: 40px 20px, -20px 30px, 30px -10px, -25px 15px, 20px -20px; transform: rotate(4deg); }
        }
        @keyframes gelembung-naik {
            0% { transform: translate(0, 0); opacity: 0; }
            15% { opacity: 1; }
            100% { transform: translate(-3px, -175px); opacity: 0; }
        }
        @keyframes ombak-galon { from { transform: translateX(0); } to { transform: translateX(-20px); } }
        @keyframes naik-turun-air { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-1.5px); } }
        @keyframes riak-air { from { transform: scale(.4); opacity: 1; } to { transform: scale(7); opacity: 0; } }

        /* ==========================================================
           Dialog QRIS
           ========================================================== */
        .dialog-qris { width: min(440px, calc(100% - 2rem)); text-align: center; }
        .qris-kepala { text-align: left; margin-bottom: 1rem; }
        .qris-kepala h2 { margin-bottom: .2rem; }
        .qris-kepala p { margin: 0; color: var(--redup); font-size: .92rem; }
        .qris-bingkai { margin: 0 auto; width: 250px; padding: 12px; border-radius: 20px; background: var(--putih); border: 2px solid var(--galon); box-shadow: 0 0 0 8px var(--embun); }
        .qris-bingkai img { display: block; width: 100%; height: auto; border-radius: 10px; }
        .qris-nominal { margin: 1.2rem 0 .3rem; background: var(--laut); color: var(--putih); border-radius: var(--radius); padding: .9rem 1rem; }
        .qris-nominal span { display: block; font-size: .85rem; color: rgba(255,255,255,.75); }
        .qris-nominal strong { font-size: 2rem; font-variant-numeric: tabular-nums; }
        .qris-petunjuk { color: var(--redup); font-size: .9rem; margin: .7rem auto 0; max-width: 36ch; }
        .qris-cek { display: inline-flex; align-items: center; gap: .5rem; background: #FFF6E5; color: #8A5A00; padding: .4rem .9rem; border-radius: 999px; font-weight: 500; font-size: .88rem; margin: .9rem 0 0; }
        .titik-tunggu { width: 9px; height: 9px; border-radius: 50%; background: var(--tutup); animation: denyut 1.2s ease-in-out infinite; }
        @keyframes denyut { 50% { opacity: .3; transform: scale(.7); } }
        .dialog-qris .kolom { text-align: left; margin-top: 1.2rem; }
        .dialog-qris .aksi-dialog { justify-content: center; }

        /* ==========================================================
           Untuk pengguna yang mematikan animasi
           ========================================================== */
        @media (prefers-reduced-motion: reduce) {
            .produk-foto *, .produk-foto::before, .produk-kartu { transition: none !important; }
            .produk-foto .sinar, .produk-foto .cincin, .produk-foto .kaustik, .kilau, .gelembung,
            .produk-foto .ombak-depan, .produk-foto .ombak-belakang, .produk-foto .isi-air, .titik-tunggu {
                animation: none !important;
            }
            .riak { display: none; }
        }
    </style>

    <header class="kepala">
        <div>
            <h1>Kasir</h1>
            <p>Pilih produk yang dibeli, lalu terima pembayaran.</p>
        </div>
        <span class="lencana">{{ now()->translatedFormat('l, d F Y') }}</span>
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

    @if ($transaksiBaru)
        <section class="nota" aria-label="Pembayaran berhasil">
            <div>
                <h2>Pembayaran diterima</h2>
                <p>
                    {{ $transaksiBaru->invoice_number }}, {{ $transaksiBaru->items->sum('quantity') }} barang,
                    total {{ $rupiah($transaksiBaru->total) }},
                    {{ $transaksiBaru->payment_method === 'qris' ? 'dibayar dengan QRIS' : 'uang diterima ' . $rupiah($transaksiBaru->cash_received) }}.
                </p>
            </div>
            <div class="kembalian">
                @if ($transaksiBaru->payment_method === 'qris')
                    <span>Total dibayar</span>
                    <strong class="angka">{{ $rupiah($transaksiBaru->total) }}</strong>
                @else
                    <span>Kembalian</span>
                    <strong class="angka">{{ $rupiah($transaksiBaru->change_amount) }}</strong>
                @endif
            </div>
        </section>
    @endif

    @if ($ringkasanTutup)
        <section class="nota pas" aria-label="Kas sudah ditutup">
            <div>
                <h2>Kas sudah ditutup</h2>
                <p>Hasil hitungan uang di laci sudah dicatat dan akan dicocokkan oleh pemilik.</p>
            </div>
            <div class="kembalian">
                <span>Uang di laci</span>
                <strong class="angka">{{ $rupiah($ringkasanTutup['aktual']) }}</strong>
            </div>
        </section>
    @endif

    <div class="kasir">
        <div>
            @if ($sesiKas)
                <section class="kas-status terbuka">
                    <span class="ikon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    </span>
                    <div class="kas-teks">
                        <h2>Kas sedang dibuka</h2>
                        <p>Sejak pukul {{ $sesiKas->opened_at->format('H:i') }}, kas awal {{ $rupiah($sesiKas->opening_cash) }}.</p>
                    </div>
                    <button type="button" class="tombol tombol-garis" data-buka-dialog="dialog-tutup">Tutup kas</button>
                </section>
            @else
                <section class="kas-status">
                    <span class="ikon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <div class="kas-teks">
                        <h2>Kas belum dibuka</h2>
                        <p>Hitung uang di laci, lalu masukkan jumlahnya untuk membuka kas.</p>
                    </div>
                    <form method="POST" action="{{ route('kasir.buka') }}" class="form-baris">
                        @csrf
                        <label for="opening_cash" class="sr-only">Uang awal di laci</label>
                        <input id="opening_cash" name="opening_cash" type="number" min="0" step="500"
                               inputmode="numeric" placeholder="Contoh: 100000" value="{{ old('opening_cash') }}" required>
                        <button type="submit" class="tombol">Buka kas</button>
                    </form>
                </section>
            @endif

            @if ($produk->isEmpty())
                <p class="kosong">Belum ada produk yang dijual. Minta pemilik menambahkan produk terlebih dahulu.</p>
            @else
                <div class="produk-grid">
                    @foreach ($produk as $item)
                        @php
                            $namaKecil = strtolower($item->name);
                            $kategori = str_contains($namaKecil, 'antar') ? 'Layanan'
                                : (str_contains($namaKecil, 'galon baru') ? 'Galon baru' : 'Isi ulang');
                        @endphp
                        <button type="button" class="produk" data-id="{{ $item->id }}" data-nama="{{ $item->name }}"
                                data-harga="{{ $item->price }}" @disabled(! $sesiKas)>
                            <div class="produk-foto" aria-hidden="true">
                                <span class="sinar"></span>
                                <span class="cincin"></span>
                                <span class="halo"></span>
                                <span class="kaustik"></span>

                                @foreach ($kilau as [$kiri, $atas, $jeda, $ukuran])
                                    <span class="kilau" style="left: {{ $kiri }}%; top: {{ $atas }}px; width: {{ $ukuran }}px; height: {{ $ukuran }}px; animation-delay: {{ $jeda }}s">
                                        <svg viewBox="0 0 10 10"><path d="M5 0 L6 4 L10 5 L6 6 L5 10 L4 6 L0 5 L4 4 Z" fill="currentColor"/></svg>
                                    </span>
                                @endforeach

                                @foreach ($gelembung as [$kiri, $ukuran, $lama, $jeda])
                                    <span class="gelembung" style="left: {{ $kiri }}%; --uk: {{ $ukuran }}px; --lama: {{ $lama }}s; --jeda: {{ $jeda + $loop->parent->index * 0.3 }}s"></span>
                                @endforeach

                                <span class="panggung"></span>

                                <svg class="galon" viewBox="0 0 60 90" style="--tunda: -{{ $loop->index * 0.7 }}s">
                                    <defs>
                                        <clipPath id="galon-{{ $item->id }}">
                                            <path d="M23 18c0 4-14 6-14 15v45c0 6 5 10 11 10h20c6 0 11-4 11-10V33c0-9-14-11-14-15z"/>
                                        </clipPath>
                                    </defs>
                                    <rect class="tutup-galon" x="21" y="3" width="18" height="8" rx="3"/>
                                    <rect x="23" y="10" width="14" height="9" rx="2" fill="#FFFFFF"/>
                                    <path class="badan-galon" d="M23 18c0 4-14 6-14 15v45c0 6 5 10 11 10h20c6 0 11-4 11-10V33c0-9-14-11-14-15z"/>
                                    <g clip-path="url(#galon-{{ $item->id }})">
                                        <g class="level-air">
                                            <g class="isi-air">
                                                <path class="ombak-belakang" d="M0 40 Q5 43 10 40 T20 40 T30 40 T40 40 T50 40 T60 40 T70 40 T80 40 T90 40 T100 40 V130 H0 Z"/>
                                                <path class="ombak-depan" d="M0 42 Q5 39 10 42 T20 42 T30 42 T40 42 T50 42 T60 42 T70 42 T80 42 T90 42 T100 42 V130 H0 Z"/>
                                            </g>
                                        </g>
                                    </g>
                                </svg>

                                <span class="produk-jumlah" hidden></span>
                            </div>
                            <div class="produk-kartu">
                                <span class="produk-kategori">{{ $kategori }}</span>
                                <span class="produk-nama">{{ $item->name }}</span>
                                <span class="produk-harga">{{ $rupiah($item->price) }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="keranjang" aria-label="Pesanan">
            <div class="keranjang-kepala">
                <h2>Pesanan</h2>
                <span id="jumlah-barang">0 barang</span>
            </div>

            <ul class="daftar-pesanan" id="daftar-pesanan" hidden></ul>

            <div class="keranjang-kosong" id="pesanan-kosong">
                <div>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h2l2.4 11.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H6.2"/><circle cx="10" cy="20" r="1.3"/><circle cx="17" cy="20" r="1.3"/>
                    </svg>
                    <strong>Belum ada pesanan</strong>
                    {{ $sesiKas ? 'Klik produk untuk menambahkannya ke pesanan.' : 'Buka kas terlebih dahulu untuk mulai mencatat pesanan.' }}
                </div>
            </div>

            <div class="keranjang-kaki">
                <div class="baris-total">
                    <span>Total</span>
                    <strong class="angka" id="total-pesanan">Rp 0</strong>
                </div>
                <div class="dua-tombol">
                    <button type="button" class="tombol" id="tombol-tunai" disabled>Bayar tunai</button>
                    <button type="button" class="tombol" id="tombol-qris" disabled>QRIS</button>
                </div>
                <button type="button" class="tautan-kosongkan" id="kosongkan" hidden>Kosongkan pesanan</button>
            </div>
        </aside>
    </div>

    {{-- Dialog bayar tunai --}}
    <dialog class="dialog" id="dialog-tunai" aria-labelledby="judul-tunai">
        <form method="POST" action="{{ route('kasir.transaksi') }}" id="form-tunai">
            @csrf
            <div id="input-items"></div>

            <h2 id="judul-tunai">Bayar tunai</h2>
            <div class="ringkas-total">
                <span>Total belanja</span>
                <strong class="angka" id="tunai-total">Rp 0</strong>
            </div>

            <div class="kolom">
                <label for="cash_received">Uang diterima</label>
                <input id="cash_received" name="cash_received" type="number" min="0" step="500" inputmode="numeric" required>
            </div>

            <div class="nominal">
                <button type="button" data-nominal="pas">Uang pas</button>
                @foreach ([10000, 20000, 50000, 100000] as $nominal)
                    <button type="button" data-nominal="{{ $nominal }}">{{ $rupiah($nominal) }}</button>
                @endforeach
            </div>

            <div class="ringkas-total kembali">
                <span>Kembalian</span>
                <strong class="angka" id="tunai-kembali">Rp 0</strong>
            </div>
            <p class="pesan-salah" id="tunai-kurang" hidden>Uang yang diterima masih kurang dari total belanja.</p>

            <div class="aksi-dialog">
                <button type="button" class="tombol tombol-garis" data-tutup-dialog>Batal</button>
                <button type="submit" class="tombol" id="simpan-tunai" disabled>Simpan pembayaran</button>
            </div>
        </form>
    </dialog>

    {{-- Dialog QRIS toko GoPay Merchant (konfirmasi manual) --}}
    <dialog class="dialog dialog-qris" id="dialog-qris" aria-labelledby="judul-qris">
        <form method="POST" action="{{ route('kasir.qris.manual') }}" id="form-qris">
            @csrf
            <div id="input-items-qris"></div>

            <div class="qris-kepala">
                <h2 id="judul-qris">Bayar dengan QRIS</h2>
                <p>Pindai dengan GoPay, DANA, OVO, ShopeePay, atau m-banking.</p>
            </div>

            @if ($adaGambarQris)
                <div class="qris-bingkai">
                    <img src="{{ asset('images/qris-toko.jpg') }}" alt="QRIS {{ config('app.name') }}">
                </div>
            @else
                <p class="pemberitahuan gagal">Gambar QRIS belum dipasang. Simpan gambar di public/images/qris-toko.jpg, atau tunjukkan QRIS dari aplikasi GoPay Merchant.</p>
            @endif

            <div class="qris-nominal">
                <span>Masukkan nominal pembayaran</span>
                <strong id="qris-total">Rp 0</strong>
            </div>
            <p class="qris-petunjuk">Pastikan pembeli memasukkan nominal yang sama persis. Untuk nominal otomatis, gunakan menu "Buat tagihan QRIS pakai nominal" di aplikasi GoPay Merchant.</p>

            <p class="qris-cek"><span class="titik-tunggu" aria-hidden="true"></span> Cek notifikasi pembayaran di aplikasi GoPay Merchant</p>

            <div class="kolom">
                <label for="referensi">Nama pembayar atau catatan (boleh dikosongkan)</label>
                <input id="referensi" name="referensi" type="text" maxlength="50" placeholder="Contoh: DANA a.n. Rina">
            </div>

            <div class="aksi-dialog">
                <button type="button" class="tombol tombol-garis" data-tutup-dialog>Batal</button>
                <button type="submit" class="tombol" id="konfirmasi-qris">Konfirmasi sudah dibayar</button>
            </div>
        </form>
    </dialog>

    {{-- Dialog tutup kas (hitung buta) --}}
    @if ($sesiKas)
        <dialog class="dialog" id="dialog-tutup" aria-labelledby="judul-tutup">
            <form method="POST" action="{{ route('kasir.tutup') }}">
                @csrf
                <h2 id="judul-tutup">Tutup kas</h2>
                <p class="dialog-sub">Hitung semua uang tunai di laci, termasuk kas awal, lalu masukkan jumlahnya. Setelah kas ditutup, angka ini tidak bisa diubah.</p>

                <div class="kolom">
                    <label for="actual_cash">Uang di laci</label>
                    <input id="actual_cash" name="actual_cash" type="number" min="0" step="500" inputmode="numeric" required>
                </div>

                <div class="kolom">
                    <label for="notes">Catatan (boleh dikosongkan)</label>
                    <textarea id="notes" name="notes" rows="2" maxlength="255" placeholder="Contoh: Rp2.000 dipakai beli plastik"></textarea>
                </div>

                <div class="aksi-dialog">
                    <button type="button" class="tombol tombol-garis" data-tutup-dialog>Batal</button>
                    <button type="submit" class="tombol">Tutup kas</button>
                </div>
            </form>
        </dialog>
    @endif

    <script>
    (() => {
        const rupiah = (n) => 'Rp ' + Number(n).toLocaleString('id-ID');
        const pesanan = new Map();
        const kurangiAnimasi = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const daftar = document.getElementById('daftar-pesanan');
        const kosong = document.getElementById('pesanan-kosong');
        const jumlahBarang = document.getElementById('jumlah-barang');
        const totalPesanan = document.getElementById('total-pesanan');
        const tombolTunai = document.getElementById('tombol-tunai');
        const tombolQris = document.getElementById('tombol-qris');
        const tombolKosongkan = document.getElementById('kosongkan');

        const hitungTotal = () => [...pesanan.values()].reduce((t, i) => t + i.harga * i.qty, 0);

        // Tinggi air di galon naik sesuai jumlah pesanan
        const tinggiAir = (qty) => (qty <= 0 ? 0 : qty === 1 ? -8 : qty === 2 ? -12 : -16);

        function tampilkan() {
            daftar.innerHTML = '';
            let jumlah = 0;

            pesanan.forEach((item, id) => {
                jumlah += item.qty;
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="teks"><div class="nama"></div><small class="angka"></small></div>
                    <div class="sub angka"></div>
                    <div class="atur">
                        <button type="button" data-aksi="kurang" aria-label="Kurangi">−</button>
                        <span class="qty angka"></span>
                        <button type="button" data-aksi="tambah" aria-label="Tambah">+</button>
                    </div>`;
                li.querySelector('.nama').textContent = item.nama;
                li.querySelector('small').textContent = rupiah(item.harga);
                li.querySelector('.qty').textContent = item.qty;
                li.querySelector('.sub').textContent = rupiah(item.harga * item.qty);
                li.querySelector('[data-aksi=kurang]').addEventListener('click', () => {
                    item.qty -= 1;
                    if (item.qty <= 0) pesanan.delete(id);
                    tampilkan();
                });
                li.querySelector('[data-aksi=tambah]').addEventListener('click', () => {
                    item.qty += 1;
                    tampilkan();
                });
                daftar.append(li);
            });

            // Kartu yang ada di pesanan berubah warna, air di galon naik, dan jumlahnya tampil
            document.querySelectorAll('.produk[data-id]').forEach((kartu) => {
                const item = pesanan.get(kartu.dataset.id);
                const lencana = kartu.querySelector('.produk-jumlah');
                kartu.classList.toggle('dipilih', Boolean(item));
                kartu.querySelector('svg.galon').style.setProperty('--naik', tinggiAir(item ? item.qty : 0) + 'px');
                lencana.hidden = !item;
                lencana.textContent = item ? item.qty : '';
            });

            const ada = pesanan.size > 0;
            daftar.hidden = !ada;
            kosong.hidden = ada;
            tombolKosongkan.hidden = !ada;
            tombolTunai.disabled = !ada;
            tombolQris.disabled = !ada;
            jumlahBarang.textContent = jumlah + ' barang';
            totalPesanan.textContent = rupiah(hitungTotal());
        }

        // Riak air dari titik yang diklik
        function buatRiak(kartu, e) {
            if (kurangiAnimasi) return;
            const foto = kartu.querySelector('.produk-foto');
            const kotak = foto.getBoundingClientRect();
            const diFoto = e.clientX > 0 && e.clientY >= kotak.top && e.clientY <= kotak.bottom;
            const riak = document.createElement('span');
            riak.className = 'riak';
            riak.style.left = (diFoto ? e.clientX - kotak.left : kotak.width / 2) + 'px';
            riak.style.top = (diFoto ? e.clientY - kotak.top : kotak.height / 2) + 'px';
            foto.append(riak);
            riak.addEventListener('animationend', () => riak.remove());
        }

        document.querySelectorAll('.produk[data-id]').forEach((tombol) => {
            tombol.addEventListener('click', (e) => {
                const id = tombol.dataset.id;
                const item = pesanan.get(id) ?? { nama: tombol.dataset.nama, harga: Number(tombol.dataset.harga), qty: 0 };
                item.qty += 1;
                pesanan.set(id, item);
                buatRiak(tombol, e);
                tampilkan();
            });
        });

        tombolKosongkan.addEventListener('click', () => {
            pesanan.clear();
            tampilkan();
        });

        document.querySelectorAll('[data-buka-dialog]').forEach((b) =>
            b.addEventListener('click', () => document.getElementById(b.dataset.bukaDialog).showModal()));
        document.querySelectorAll('[data-tutup-dialog]').forEach((b) =>
            b.addEventListener('click', () => b.closest('dialog').close()));

        // Isi pesanan ke form sebagai input tersembunyi sebelum dikirim
        function isiItems(wadah) {
            wadah.innerHTML = '';
            let i = 0;
            pesanan.forEach((item, id) => {
                [['product_id', id], ['quantity', item.qty]].forEach(([kunci, nilai]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `items[${i}][${kunci}]`;
                    input.value = nilai;
                    wadah.append(input);
                });
                i += 1;
            });
        }

        // ---------- Bayar tunai ----------
        const dialogTunai = document.getElementById('dialog-tunai');
        const inputUang = document.getElementById('cash_received');
        const tunaiTotal = document.getElementById('tunai-total');
        const tunaiKembali = document.getElementById('tunai-kembali');
        const tunaiKurang = document.getElementById('tunai-kurang');
        const simpanTunai = document.getElementById('simpan-tunai');

        function cekUang() {
            const total = hitungTotal();
            const uang = Number(inputUang.value || 0);
            const cukup = inputUang.value !== '' && uang >= total;
            tunaiKembali.textContent = rupiah(cukup ? uang - total : 0);
            tunaiKurang.hidden = inputUang.value === '' || cukup;
            simpanTunai.disabled = !cukup;
        }

        tombolTunai.addEventListener('click', () => {
            tunaiTotal.textContent = rupiah(hitungTotal());
            inputUang.value = '';
            cekUang();
            dialogTunai.showModal();
            inputUang.focus();
        });

        inputUang.addEventListener('input', cekUang);

        document.querySelectorAll('[data-nominal]').forEach((b) => b.addEventListener('click', () => {
            inputUang.value = b.dataset.nominal === 'pas' ? hitungTotal() : b.dataset.nominal;
            cekUang();
        }));

        document.getElementById('form-tunai').addEventListener('submit', () => {
            isiItems(document.getElementById('input-items'));
            simpanTunai.disabled = true;
            simpanTunai.textContent = 'Menyimpan...';
        });

        // ---------- QRIS GoPay Merchant (konfirmasi manual) ----------
        const dialogQris = document.getElementById('dialog-qris');
        const tombolKonfirmasi = document.getElementById('konfirmasi-qris');

        tombolQris.addEventListener('click', () => {
            document.getElementById('qris-total').textContent = rupiah(hitungTotal());
            document.getElementById('referensi').value = '';
            dialogQris.showModal();
        });

        document.getElementById('form-qris').addEventListener('submit', (e) => {
            const yakin = confirm('Pastikan pembayaran ' + rupiah(hitungTotal()) + ' sudah masuk di aplikasi GoPay Merchant. Konfirmasi sekarang?');
            if (!yakin) {
                e.preventDefault();
                return;
            }
            isiItems(document.getElementById('input-items-qris'));
            tombolKonfirmasi.disabled = true;
            tombolKonfirmasi.textContent = 'Menyimpan...';
        });
    })();
    </script>
@endsection