<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b315e">
    <title>{{ config('app.name', 'Sistem Pengajuan ID Card') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset(config('mitra.logo')) }}">
</head>
<body class="auth-body">
    <a href="#auth-form" class="skip-link">Lewati ke formulir</a>
    <main class="auth-shell">
        <section class="auth-intro">
            <a href="{{ url('/') }}" class="auth-brand">SISTEM PENGAJUAN ID CARD <span>Portal pengajuan dan penerbitan ID Card</span></a>
            <div><p class="auth-kicker">Administrasi kerja, lebih terarah</p><h1>Satu pengajuan.<br>Proses yang jelas.</h1><p>Ajukan izin, pantau pemeriksaan, dan telusuri keputusan Humas, K3, serta Keamanan dalam satu tempat.</p></div>
            <ol class="auth-steps"><li><span>01</span> Lengkapi data pekerjaan</li><li><span>02</span> Tinjauan tiga bagian</li><li><span>03</span> Pantau hasil &amp; riwayat</li></ol>
        </section>
        <section class="auth-panel" id="auth-form" tabindex="-1">
            <div class="auth-form-wrap">{{ $slot }}</div>
            <p class="auth-footnote">Gunakan akun pribadi Anda. Jangan bagikan kata sandi kepada orang lain.</p>
        </section>
    </main>
</body>
</html>
