<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/depot.css') }}">
</head>
<body>
<div class="login">
    <section class="login-sisi">
        <div class="merek">
            @include('partials.tetes')
            <span>{{ config('app.name') }}</span>
        </div>

        <div class="galon-wadah">
            {{-- Galon yang terisi air saat halaman dibuka --}}
            <svg class="galon" viewBox="0 0 220 300" role="img" aria-label="Galon air yang sedang terisi">
                <defs>
                    <clipPath id="isi-galon">
                        <path d="M85 58 C85 70 40 76 40 106 V256 C40 278 58 290 80 290 H140 C162 290 180 278 180 256 V106 C180 76 135 70 135 58 Z"/>
                    </clipPath>
                </defs>

                <rect class="tutup" x="78" y="14" width="64" height="26" rx="9"/>
                <rect class="leher" x="86" y="38" width="48" height="22" rx="4"/>
                <path class="badan" d="M85 58 C85 70 40 76 40 106 V256 C40 278 58 290 80 290 H140 C162 290 180 278 180 256 V106 C180 76 135 70 135 58 Z"/>

                <g clip-path="url(#isi-galon)">
                    <g class="isi-naik">
                        <path class="ombak-2" d="M0 128 Q27.5 116 55 128 T110 128 T165 128 T220 128 T275 128 T330 128 T385 128 T440 128 V300 H0 Z"/>
                        <path class="ombak-1" d="M0 136 Q27.5 124 55 136 T110 136 T165 136 T220 136 T275 136 T330 136 T385 136 T440 136 V300 H0 Z"/>
                    </g>
                </g>

                <path class="rusuk" d="M44 170 H176 M44 214 H176"/>
                <rect class="kilau" x="56" y="112" width="10" height="130" rx="5"/>
            </svg>
        </div>

        <div>
            <h1>Air bersih setiap hari, kas rapi setiap tutup toko.</h1>
            <p>Catat penjualan galon, terima pembayaran tunai dan QRIS, lalu cocokkan uang di laci dalam satu tempat.</p>
        </div>
    </section>

    <section class="login-form">
        <div class="kartu-login">
            <h2>Masuk</h2>
            <p class="sub">Gunakan akun pemilik atau kasir depot.</p>

            @if (session('status'))
                <div class="info" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ url('/login') }}" novalidate>
                @csrf

                <div class="kolom">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           autocomplete="username" autofocus required
                           @error('email') aria-invalid="true" aria-describedby="email-salah" @enderror>
                    @error('email')
                        <span class="pesan-salah" id="email-salah">{{ $message }}</span>
                    @enderror
                </div>

                <div class="kolom">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password"
                           autocomplete="current-password" required
                           @error('password') aria-invalid="true" aria-describedby="password-salah" @enderror>
                    @error('password')
                        <span class="pesan-salah" id="password-salah">{{ $message }}</span>
                    @enderror
                </div>

                <label class="centang">
                    <input type="checkbox" name="remember" value="1">
                    Ingat saya di perangkat ini
                </label>

                <button type="submit" class="tombol tombol-penuh">Masuk</button>
            </form>
        </div>
    </section>
</div>
</body>
</html>
