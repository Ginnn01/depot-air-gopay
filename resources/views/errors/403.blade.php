<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses ditolak - {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/depot.css') }}">
</head>
<body>
    <div class="error-wadah">
        <div>
            <svg viewBox="0 0 32 32" width="56" height="56" aria-hidden="true">
                <path d="M16 3 C16 3 6 14.5 6 20.5 A10 10 0 0 0 26 20.5 C26 14.5 16 3 16 3 Z" fill="#CDE8F1"/>
            </svg>
            <h1>Halaman ini khusus pemilik</h1>
            <p>Akun Anda tidak punya akses ke halaman ini. Kembali ke halaman kasir untuk melanjutkan pekerjaan.</p>
            <a href="{{ url('/') }}" class="tombol">Kembali ke halaman saya</a>
        </div>
    </div>
</body>
</html>
