<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Panel pengamanan akses</p><h1 class="ui-page-title">Dashboard Keamanan</h1><p class="ui-page-subtitle">Periksa informasi pemohon dan pekerjaan sebelum mengambil keputusan.</p></div>
            <span class="ui-role-chip">Bagian Keamanan</span>
        </div>
    </x-slot>
    <div class="ui-page">
        @if (session('success')) <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div> @endif
        <section class="ui-hero">
            <div class="ui-hero-copy"><p class="ui-eyebrow">Area kerja Keamanan</p><h2 class="ui-hero-title">Selamat datang, {{ auth()->user()->name }}.</h2><p class="ui-hero-text">Tinjau identitas, perusahaan, lokasi, dan periode kerja pada setiap permohonan. Simpan keputusan agar proses persetujuan dapat ditelusuri.</p><div class="ui-hero-actions"><a href="{{ route('admin.permohonan.index') }}" class="ui-button ui-button-light">Buka Antrean Keamanan <span aria-hidden="true">→</span></a></div></div>
            <div class="ui-hero-side" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M32 6 52 14v15c0 13-8 22-20 29C20 51 12 42 12 29V14L32 6Z"/><path d="M22 32h20M32 22v20"/></svg></div>
        </section>
        @include('dashboards.summary')
        <section class="ui-card">
            <h2 class="ui-card-title">Semua Pendaftar &amp; Riwayat Pendaftaran</h2>
            <p class="ui-card-text">Lihat seluruh pengajuan, termasuk yang masih ditinjau, disetujui, dan ditolak, beserta keputusan setiap bagian.</p>
            <a href="{{ route('admin.permohonan.history') }}" class="ui-card-link">Buka pendaftar &amp; riwayat →</a>
        </section>
        <section class="ui-grid ui-grid-2">
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></div><h3 class="ui-card-title">Verifikasi permohonan</h3><p class="ui-card-text">Buka antrean untuk melihat detail pemohon, perusahaan, lokasi, dan masa kerja yang diajukan.</p><a href="{{ route('admin.permohonan.index') }}" class="ui-card-link">Periksa permohonan <span aria-hidden="true">→</span></a></article>
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14v16H5z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg></div><h3 class="ui-card-title">Riwayat keputusan</h3><p class="ui-card-text">Keputusan setiap bagian dicatat bersama nama admin, waktu keputusan, dan catatan yang diberikan.</p></article>
        </section>
    </div>
</x-app-layout>
