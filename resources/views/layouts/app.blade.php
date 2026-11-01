@php
    $pengguna = auth()->user();
    $inisial = collect(explode(' ', $pengguna->name))
        ->filter()
        ->map(fn ($kata) => mb_strtoupper(mb_substr($kata, 0, 1)))
        ->take(2)
        ->implode('');
    $keteranganPeran = $pengguna->role === 'kasir' && $pengguna->shift
        ? 'Kasir shift ' . $pengguna->shift
        : ucfirst($pengguna->role);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/depot.css') }}">
</head>
<body>
<div class="aplikasi">
    <aside class="sidebar">
        <a href="{{ route('home') }}" class="merek">
            @include('partials.tetes')
            <span>{{ config('app.name') }}</span>
        </a>

        <nav class="nav" aria-label="Menu utama">
            @if ($pengguna->role === 'pemilik')
                <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="9" rx="2"/><rect x="14" y="3" width="7" height="5" rx="2"/>
                        <rect x="14" y="12" width="7" height="9" rx="2"/><rect x="3" y="16" width="7" height="5" rx="2"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('riwayat-kas') }}" @if (request()->routeIs('riwayat-kas*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V3h6v1"/><path d="m9 13 2 2 4-4"/>
                    </svg>
                    Riwayat Kas
                </a>
                <a href="{{ route('kelola-kasir') }}" @if (request()->routeIs('kelola-kasir*')) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/>
                        <path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18 14.5a6.5 6.5 0 0 1 3.5 5.5"/>
                    </svg>
                    Kasir &amp; Shift
                </a>
            @endif
            <a href="{{ route('kasir') }}" @if (request()->routeIs('kasir')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="10" width="18" height="11" rx="2"/><path d="M7 10V5a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v5"/>
                    <path d="M7 14h2M11 14h2M15 14h2M7 17h10"/>
                </svg>
                Kasir
            </a>
        </nav>

        <svg class="sidebar-ombak" viewBox="0 0 250 160" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0 70 C40 50 80 90 125 70 S210 50 250 70 V160 H0 Z" fill="#1E9BC4" opacity=".35"/>
            <path d="M0 100 C50 80 90 120 140 100 S220 85 250 100 V160 H0 Z" fill="#1E9BC4" opacity=".45"/>
        </svg>

        <div class="akun">
            <span class="inisial" aria-hidden="true">{{ $inisial }}</span>
            <div class="akun-teks">
                <div class="nama">{{ $pengguna->name }}</div>
                <div class="peran">{{ $keteranganPeran }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="tombol-keluar" title="Keluar" aria-label="Keluar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <main class="isi">
        <div class="isi-dalam">
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>