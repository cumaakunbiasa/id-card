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
<body class="app-body antialiased">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <div class="min-h-screen">
        @include('layouts.navigation')

        @isset($header)
            <header class="app-page-header">
                <div class="app-page-header-inner">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main id="main-content" class="app-main" tabindex="-1">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
