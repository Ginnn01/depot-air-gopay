@extends('layouts.app')

@section('title', 'Kasir & Shift')

@php
    $inisialDari = fn ($nama) => collect(explode(' ', $nama))->filter()
        ->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->take(2)->implode('');

    $kelompokShift = [
        ['kunci' => 'pagi', 'judul' => 'Shift pagi', 'daftar' => $kasirPagi],
        ['kunci' => 'malam', 'judul' => 'Shift malam', 'daftar' => $kasirMalam],
    ];
@endphp

@section('content')
    <style>
        .kelola { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(290px, 1fr); gap: 1.5rem; align-items: start; }

        .kelompok-shift { margin-bottom: 1.8rem; }
        .kepala-shift { display: flex; align-items: center; gap: .75rem; margin-bottom: .9rem; }
        .ikon-shift { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; flex: none; }
        .ikon-shift svg { width: 22px; height: 22px; }
        .ikon-shift.pagi { background: #FFF1D6; color: #B36B00; }
        .ikon-shift.malam { background: #DDE8F0; color: var(--laut); }
        .kepala-shift h2 { font-size: 1.1rem; }
        .kepala-shift .jumlah { margin-left: auto; color: var(--redup); font-size: .9rem; }

        .daftar-kasir { display: grid; gap: .7rem; }
        .kartu-kasir {
            display: flex; align-items: center; gap: .9rem; flex-wrap: wrap;
            background: var(--putih); border: 1px solid var(--garis); border-left: 5px solid var(--garis);
            border-radius: var(--radius-besar); padding: .9rem 1.1rem;
        }
        .kartu-kasir.pagi { border-left-color: var(--tutup); }
        .kartu-kasir.malam { border-left-color: var(--laut); }
        .kartu-kasir .inisial { width: 42px; height: 42px; }
        .kartu-kasir.malam .inisial { background: var(--laut); color: var(--putih); }
        .kartu-kasir .info { flex: 1; min-width: 160px; line-height: 1.35; }
        .kartu-kasir .info strong { display: block; }
        .kartu-kasir .info small { display: block; color: var(--redup); font-size: .85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .status-akun { font-size: .78rem; font-weight: 500; padding: .15rem .65rem; border-radius: 999px; background: #DDF1E6; color: var(--hijau); }
        .kartu-kasir.nonaktif { background: #F7FAFB; }
        .kartu-kasir.nonaktif .info, .kartu-kasir.nonaktif .inisial { opacity: .55; }
        .kartu-kasir.nonaktif .status-akun { background: #F8E0DD; color: var(--merah); }
        .aksi-kasir { display: flex; align-items: center; gap: .3rem; }
        .aksi-kasir form { margin: 0; }
        .tombol-teks { background: none; border: 0; font: inherit; font-size: .88rem; font-weight: 500; cursor: pointer; padding: .45rem .55rem; border-radius: var(--radius-kecil); color: var(--merah); }
        .tombol-teks:hover { background: var(--embun); }
        .tombol-teks.aktifkan { color: var(--hijau); }
        .kosong-shift { background: var(--putih); border: 1.5px dashed var(--garis); border-radius: var(--radius-besar); padding: 1rem 1.2rem; color: var(--redup); margin: 0; }

        .form-kasir { position: sticky; top: 1.5rem; }
        .form-kasir h2 { font-size: 1.15rem; margin-bottom: .25rem; }
        .form-kasir > p { color: var(--redup); margin: 0 0 1.2rem; font-size: .92rem; }
        .kolom input[type=email], .kolom input[type=password], .kolom input[type=text] { width: 100%; }

        .pilih-shift { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; border: 0; padding: 0; margin: 0 0 1.3rem; }
        .pilih-shift legend { font-weight: 500; font-size: .95rem; margin-bottom: .45rem; padding: 0; }
        .opsi-shift {
            position: relative; display: flex; align-items: center; gap: .55rem; cursor: pointer;
            padding: .8rem .9rem; border: 1.5px solid var(--garis); border-radius: var(--radius); font-weight: 500; background: var(--putih);
        }
        .opsi-shift input { position: absolute; opacity: 0; pointer-events: none; }
        .opsi-shift svg { width: 20px; height: 20px; }
        .opsi-shift.pagi svg { color: #B36B00; }
        .opsi-shift.malam svg { color: var(--laut); }
        .opsi-shift:has(input:checked) { border-color: var(--air); background: var(--embun); box-shadow: 0 0 0 3px rgba(30,155,196,.15); }
        .opsi-shift:has(input:focus-visible) { outline: 3px solid var(--tutup); outline-offset: 2px; }

        @media (max-width: 1050px) {
            .kelola { grid-template-columns: 1fr; }
            .form-kasir { position: static; }
        }
    </style>

    <header class="kepala">
        <div>
            <h1>Kasir &amp; shift</h1>
            <p>Atur siapa yang bertugas di shift pagi dan shift malam.</p>
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

    <div class="kelola">
        <div>
            @foreach ($kelompokShift as $kelompok)
                <section class="kelompok-shift" aria-labelledby="judul-{{ $kelompok['kunci'] }}">
                    <div class="kepala-shift">
                        <span class="ikon-shift {{ $kelompok['kunci'] }}" aria-hidden="true">
                            @if ($kelompok['kunci'] === 'pagi')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                            @else
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
                            @endif
                        </span>
                        <h2 id="judul-{{ $kelompok['kunci'] }}">{{ $kelompok['judul'] }}</h2>
                        <span class="jumlah">{{ $kelompok['daftar']->where('is_active', true)->count() }} kasir aktif</span>
                    </div>

                    @if ($kelompok['daftar']->isEmpty())
                        <p class="kosong-shift">Belum ada kasir di {{ strtolower($kelompok['judul']) }}. Tambahkan lewat formulir di samping.</p>
                    @else
                        <div class="daftar-kasir">
                            @foreach ($kelompok['daftar'] as $k)
                                <article class="kartu-kasir {{ $k->shift }} {{ $k->is_active ? '' : 'nonaktif' }}">
                                    <span class="inisial" aria-hidden="true">{{ $inisialDari($k->name) }}</span>
                                    <div class="info">
                                        <strong>{{ $k->name }}</strong>
                                        <small>{{ $k->email }}</small>
                                        <small>{{ $k->cash_sessions_count }} kali membuka kas</small>
                                    </div>
                                    <span class="status-akun">{{ $k->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    <div class="aksi-kasir">
                                        <button type="button" class="tombol tombol-garis tombol-kecil"
                                                data-ubah="{{ json_encode(['id' => $k->id, 'name' => $k->name, 'email' => $k->email, 'shift' => $k->shift]) }}">
                                            Ubah
                                        </button>
                                        <form method="POST" action="{{ route('kelola-kasir.status', $k) }}"
                                              onsubmit="return confirm('{{ $k->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $k->name }}?')">
                                            @csrf
                                            <button type="submit" class="tombol-teks {{ $k->is_active ? '' : 'aktifkan' }}">
                                                {{ $k->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        <section class="panel form-kasir" aria-labelledby="judul-tambah">
            <h2 id="judul-tambah">Tambah kasir</h2>
            <p>Kasir baru bisa langsung login dengan email dan kata sandi ini.</p>

            <form method="POST" action="{{ route('kelola-kasir.store') }}">
                @csrf
                <div class="kolom">
                    <label for="name">Nama kasir</label>
                    <input id="name" name="name" type="text" maxlength="100" value="{{ old('name') }}" placeholder="Contoh: Rizki" required>
                </div>
                <div class="kolom">
                    <label for="email">Email untuk login</label>
                    <input id="email" name="email" type="email" maxlength="150" value="{{ old('email') }}" placeholder="Contoh: rizki@depot.test" required>
                </div>
                <div class="kolom">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" minlength="6" placeholder="Minimal 6 karakter" required>
                </div>

                <fieldset class="pilih-shift">
                    <legend>Shift</legend>
                    <label class="opsi-shift pagi">
                        <input type="radio" name="shift" value="pagi" @checked(old('shift', 'pagi') === 'pagi')>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                        Pagi
                    </label>
                    <label class="opsi-shift malam">
                        <input type="radio" name="shift" value="malam" @checked(old('shift') === 'malam')>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
                        Malam
                    </label>
                </fieldset>

                <button type="submit" class="tombol tombol-penuh">Tambah kasir</button>
            </form>
        </section>
    </div>

    {{-- Dialog ubah data kasir --}}
    <dialog class="dialog" id="dialog-ubah" aria-labelledby="judul-ubah">
        <form method="POST" id="form-ubah">
            @csrf
            @method('PUT')
            <h2 id="judul-ubah">Ubah data kasir</h2>
            <p class="dialog-sub">Kosongkan kata sandi kalau tidak ingin menggantinya.</p>

            <div class="kolom">
                <label for="ubah-name">Nama kasir</label>
                <input id="ubah-name" name="name" type="text" maxlength="100" required>
            </div>
            <div class="kolom">
                <label for="ubah-email">Email untuk login</label>
                <input id="ubah-email" name="email" type="email" maxlength="150" required>
            </div>
            <div class="kolom">
                <label for="ubah-password">Kata sandi baru</label>
                <input id="ubah-password" name="password" type="password" minlength="6" placeholder="Biarkan kosong kalau tidak diganti">
            </div>

            <fieldset class="pilih-shift">
                <legend>Shift</legend>
                <label class="opsi-shift pagi">
                    <input type="radio" name="shift" value="pagi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    Pagi
                </label>
                <label class="opsi-shift malam">
                    <input type="radio" name="shift" value="malam">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/></svg>
                    Malam
                </label>
            </fieldset>

            <div class="aksi-dialog">
                <button type="button" class="tombol tombol-garis" data-tutup-dialog>Batal</button>
                <button type="submit" class="tombol">Simpan perubahan</button>
            </div>
        </form>
    </dialog>

    <script>
    (() => {
        const dialog = document.getElementById('dialog-ubah');
        const form = document.getElementById('form-ubah');
        const alamatUbah = @json(route('kelola-kasir.update', ['user' => '__ID__']));

        document.querySelectorAll('[data-ubah]').forEach((tombol) => {
            tombol.addEventListener('click', () => {
                const kasir = JSON.parse(tombol.dataset.ubah);
                form.action = alamatUbah.replace('__ID__', kasir.id);
                form.querySelector('[name=name]').value = kasir.name;
                form.querySelector('[name=email]').value = kasir.email;
                form.querySelector('[name=password]').value = '';
                form.querySelectorAll('[name=shift]').forEach((pilihan) => {
                    pilihan.checked = pilihan.value === (kasir.shift || 'pagi');
                });
                dialog.showModal();
            });
        });

        document.querySelectorAll('[data-tutup-dialog]').forEach((tombol) =>
            tombol.addEventListener('click', () => tombol.closest('dialog').close()));
    })();
    </script>
@endsection