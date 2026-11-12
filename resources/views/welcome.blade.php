<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b315e">
    <title>Sistem Pengajuan ID Card</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset(config('mitra.logo')) }}">
</head>

<body class="landing-body">
    <header class="landing-nav">
        <a class="app-brand" href="{{ url('/') }}">
            <span class="app-brand-mark"><img src="{{ asset(config('mitra.logo')) }}" alt="Logo PLN Indonesia Power"></span>
            <span class="app-brand-copy"><strong>SISTEM PENGAJUAN ID CARD</strong><small>Portal Pengajuan dan Penerbitan ID Card</small></span>
        </a>
        <div style="display:flex;gap:9px;flex-wrap:wrap">
            @auth
            <a href="{{ route('dashboard') }}" class="ui-button ui-button-primary">Buka Dashboard <span aria-hidden="true">→</span></a>
            @else
            @if (Route::has('login')) <a href="{{ route('login') }}" class="ui-button ui-button-outline">Masuk</a> @endif
            @if (Route::has('register')) <a href="{{ route('register') }}" class="ui-button ui-button-primary">Daftar Akun</a> @endif
            @endauth
        </div>
    </header>

    <main class="landing-main">
        <section class="landing-hero">
            <div>
                <p class="ui-eyebrow" style="color:#a9dcff">DIGITALISASI PENGELOLAAN KEAMANAN</p>
                <h1>Pengajuan id card pekerja.</h1>
                <p>Pembuatan kartu identitas pekerja secara digital, melalui prosedur pemeriksaan dari Keamanan, Humas, dan K3L</p>
                <div class="ui-hero-actions">
                    @auth
                    <a href="{{ route('dashboard') }}" class="ui-button ui-button-light">Masuk ke Dashboard <span aria-hidden="true">→</span></a>
                    @else
                    @if (Route::has('login')) <a href="{{ route('login') }}" class="ui-button ui-button-light">Masuk ke Sistem <span aria-hidden="true">→</span></a> @endif
                    @if (Route::has('register')) <a href="{{ route('register') }}" class="ui-button" style="color:#fff;border-color:rgba(255,255,255,.35);background:rgba(255,255,255,.08)">Buat Akun</a> @endif
                    @endauth
                </div>
            </div>
            <div class="landing-illustration" aria-hidden="true">
                <svg viewBox="0 0 260 220" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="63" y="24" width="134" height="171" rx="17" fill="rgba(255,255,255,.06)" stroke="rgba(223,243,255,.85)" />
                    <path d="M91 58h78M91 75h54M91 113h78M91 130h78M91 147h48" stroke="rgba(223,243,255,.7)" />
                    <circle cx="167" cy="165" r="25" fill="#0e5c99" stroke="#dff3ff" />
                    <path d="m155 165 8 8 16-18" stroke-width="4" />
                    <path d="M34 58h13M40 51v14M210 88h15M217.5 80.5v15" stroke="#8dd8ff" />
                    <circle cx="42" cy="151" r="5" fill="#8dd8ff" stroke="none" />
                    <circle cx="222" cy="42" r="4" fill="#8dd8ff" stroke="none" />
                </svg>
            </div>
        </section>

        <section class="landing-feature-grid" aria-label="Fitur sistem">
            <article class="landing-feature">
                <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 4h8l3 3v13H5V4h3Z" />
                        <path d="M8 11h8M8 15h5" />
                    </svg></div>
                <h2>Pengajuan digital</h2>
                <p>Informasi pekerja, perusahaan, lokasi, deskripsi, dan periode kerja dikumpulkan melalui formulir terstruktur.</p>
            </article>
            <article class="landing-feature">
                <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 19 6v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3Z" />
                        <path d="m9 12 2 2 4-4" />
                    </svg></div>
                <h2>Persetujuan berjenjang</h2>
                <p>Humas, K3, dan Keamanan mencatat keputusan masing-masing dengan riwayat yang dapat ditelusuri.</p>
            </article>
            <article class="landing-feature">
                <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <circle cx="8" cy="11" r="2" />
                        <path d="M13 10h5M13 14h5" />
                    </svg></div>
                <h2>Pengelolaan ID Card</h2>
                <p>Operator dapat meninjau kartu untuk permohonan yang sudah memperoleh seluruh persetujuan.</p>
            </article>
        </section>
        <footer class="landing-footer">Sistem Pengajuan ID Card · Portal pengajuan dan penerbitan kartu identitas</footer>
    </main>
</body>

</html>