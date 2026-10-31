@extends('layouts.app')

@section('title', 'Kasir')

@php
    $rupiah = fn ($angka) => 'Rp ' . number_format($angka, 0, ',', '.');
    $ringkasanTutup = session('ringkasan_tutup');
    $adaGambarQris = file_exists(public_path('images/qris-toko.jpg'));
@endphp

@section('content')
    <style>
        /* ---------- Kartu produk ---------- */
        .produk-grid { grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.4rem 1.2rem; }
        button.produk { background: transparent; border: 0; border-radius: 0; overflow: visible; display: block; padding: 0 0 .3rem; text-align: left; }
        button.produk:hover:not(:disabled) { border-color: transparent; }
        .produk-foto {
            position: relative; height: 150px; border-radius: 20px; overflow: hidden;
            display: grid; place-items: start center; padding-top: 14px;
            background: radial-gradient(120% 90% at 30% 10%, #FFFFFF 0%, var(--galon) 60%, #9FD3E6 100%);
        }
        .produk-foto svg { height: 88px; width: auto; transition: transform .25s; }
        .produk-foto .tutup-galon { fill: var(--tutup); }
        .produk-foto .badan-galon { fill: rgba(255,255,255,.85); stroke: var(--laut-2); stroke-width: 2; }
        button.produk:hover:not(:disabled) .produk-foto svg { transform: translateY(-4px) rotate(-3deg); }
        .produk-kartu {
            position: relative; margin: -44px 12px 0; padding: .9rem 1rem 1rem;
            background: var(--putih); border-radius: 16px; box-shadow: 0 10px 24px rgba(11,60,93,.12);
            display: grid; gap: .15rem; transition: background .2s, transform .2s;
        }
        button.produk:hover:not(:disabled) .produk-kartu { transform: translateY(-3px); }
        .produk-kategori { font-size: .8rem; color: var(--air); }
        .produk-nama { font-weight: 600; color: var(--tinta); line-height: 1.3; }
        .produk-harga { margin-top: .35rem; font-weight: 600; color: var(--laut-2); font-variant-numeric: tabular-nums; }
        button.produk.dipilih .produk-kartu { background: var(--air); }
        button.produk.dipilih .produk-kategori,
        button.produk.dipilih .produk-nama,
        button.produk.dipilih .produk-harga { color: var(--putih); }
        .produk-jumlah {
            position: absolute; top: 10px; right: 10px; min-width: 30px; height: 30px; padding: 0 .55rem;
            border-radius: 999px; background: var(--tutup); color: var(--laut-gelap);
            display: grid; place-items: center; font-weight: 700; font-size: .9rem;
        }
        .produk-jumlah[hidden] { display: none; }

        /* ---------- Air di galon bergoyang ---------- */
        .produk-foto .ombak-depan { fill: #1E9BC4; animation: ombak-galon 2.4s linear infinite; animation-delay: var(--tunda, 0s); }
        .produk-foto .ombak-belakang { fill: #5CC3E2; opacity: .6; animation: ombak-galon 3.6s linear infinite reverse; animation-delay: var(--tunda, 0s); }
        .produk-foto .isi-air { animation: naik-turun-air 3s ease-in-out infinite; animation-delay: var(--tunda, 0s); }
        @keyframes ombak-galon { from { transform: translateX(0); } to { transform: translateX(-20px); } }
        @keyframes naik-turun-air { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-1.5px); } }

        /* ---------- Tombol kirim rekap ke WhatsApp ---------- */
        .tombol-rekap { margin-top: .9rem; background: var(--putih); color: #1C6A42; }
        .tombol-rekap:hover { background: #E6F5EC; }
        .tombol-rekap svg { width: 18px; height: 18px; }

        /* ---------- Dialog QRIS ---------- */
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

        /* ---------- Untuk pengguna yang mematikan animasi ---------- */
        @media (prefers-reduced-motion: reduce) {
            .produk-foto svg, .produk-kartu { transition: none; }
            .produk-foto .ombak-depan, .produk-foto .ombak-belakang, .produk-foto .isi-air, .titik-tunggu { animation: none; }
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
        @php
            $r = $ringkasanTutup;
            $nomorPemilik = preg_replace('/\D/', '', (string) config('services.whatsapp.pemilik'));
            $pesanRekap = isset($r['tanggal'])
                ? implode("\n", array_filter([
                    '*Rekap tutup kas ' . config('app.name') . '*',
                    $r['tanggal'],
                    'Kasir: ' . $r['kasir'] . ', pukul ' . $r['dibuka'] . ' sampai ' . $r['ditutup'],
                    '',
                    'Transaksi lunas: ' . $r['transaksi'],
                    'Kas awal: ' . $rupiah($r['kas_awal']),
                    'Penjualan tunai: ' . $rupiah($r['tunai']),
                    'Penjualan QRIS: ' . $rupiah($r['qris']),
                    'Uang di laci: ' . $rupiah($r['aktual']),
                    $r['catatan'] ? 'Catatan kasir: ' . $r['catatan'] : null,
                    '',
                    'Detail selisih bisa dicek di menu Riwayat Kas.',
                ], fn ($baris) => $baris !== null))
                : null;
            $linkRekap = ($nomorPemilik && $pesanRekap)
                ? 'https://wa.me/' . $nomorPemilik . '?text=' . rawurlencode($pesanRekap)
                : null;
        @endphp
        <section class="nota pas" aria-label="Kas sudah ditutup">
            <div>
                <h2>Kas sudah ditutup</h2>
                <p>Hasil hitungan uang di laci sudah dicatat dan akan dicocokkan oleh pemilik.</p>
                @if ($linkRekap)
                    <a href="{{ $linkRekap }}" target="_blank" rel="noopener" class="tombol tombol-rekap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>
                        </svg>
                        Kirim rekap ke WhatsApp pemilik
                    </a>
                @endif
            </div>
            <div class="kembalian">
                <span>Uang di laci</span>
                <strong class="angka">{{ $rupiah($r['aktual']) }}</strong>
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
                                <svg viewBox="0 0 60 90" style="--tunda: -{{ $loop->index * 0.7 }}s">
                                    <defs>
                                        <clipPath id="galon-{{ $item->id }}">
                                            <path d="M23 18c0 4-14 6-14 15v45c0 6 5 10 11 10h20c6 0 11-4 11-10V33c0-9-14-11-14-15z"/>
                                        </clipPath>
                                    </defs>
                                    <rect class="tutup-galon" x="21" y="3" width="18" height="8" rx="3"/>
                                    <rect x="23" y="10" width="14" height="9" rx="2" fill="#FFFFFF"/>
                                    <path class="badan-galon" d="M23 18c0 4-14 6-14 15v45c0 6 5 10 11 10h20c6 0 11-4 11-10V33c0-9-14-11-14-15z"/>
                                    <g clip-path="url(#galon-{{ $item->id }})">
                                        <g class="isi-air">
                                            <path class="ombak-belakang" d="M0 40 Q5 43 10 40 T20 40 T30 40 T40 40 T50 40 T60 40 T70 40 T80 40 T90 40 T100 40 V90 H0 Z"/>
                                            <path class="ombak-depan" d="M0 42 Q5 39 10 42 T20 42 T30 42 T40 42 T50 42 T60 42 T70 42 T80 42 T90 42 T100 42 V90 H0 Z"/>
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

        const daftar = document.getElementById('daftar-pesanan');
        const kosong = document.getElementById('pesanan-kosong');
        const jumlahBarang = document.getElementById('jumlah-barang');
        const totalPesanan = document.getElementById('total-pesanan');
        const tombolTunai = document.getElementById('tombol-tunai');
        const tombolQris = document.getElementById('tombol-qris');
        const tombolKosongkan = document.getElementById('kosongkan');

        const hitungTotal = () => [...pesanan.values()].reduce((t, i) => t + i.harga * i.qty, 0);

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

            // Kartu produk yang ada di pesanan berubah biru dan menampilkan jumlahnya
            document.querySelectorAll('.produk[data-id]').forEach((kartu) => {
                const item = pesanan.get(kartu.dataset.id);
                const lencana = kartu.querySelector('.produk-jumlah');
                kartu.classList.toggle('dipilih', Boolean(item));
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

        document.querySelectorAll('.produk[data-id]').forEach((tombol) => {
            tombol.addEventListener('click', () => {
                const id = tombol.dataset.id;
                const item = pesanan.get(id) ?? { nama: tombol.dataset.nama, harga: Number(tombol.dataset.harga), qty: 0 };
                item.qty += 1;
                pesanan.set(id, item);
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

    @includeIf('partials.wa-pesanan')
@endsection