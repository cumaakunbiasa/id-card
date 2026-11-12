<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Panel keselamatan kerja</p><h1 class="ui-page-title">Dashboard K3</h1><p class="ui-page-subtitle">Tinjau informasi pekerjaan sebelum memberikan keputusan keselamatan.</p></div>
            <span class="ui-role-chip">Bagian K3</span>
        </div>
    </x-slot>
    <div class="ui-page">
        @if (session('success')) <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div> @endif
        <section class="ui-hero">
            <div class="ui-hero-copy"><p class="ui-eyebrow">Area kerja K3</p><h2 class="ui-hero-title">Selamat bertugas, {{ auth()->user()->name }}.</h2><p class="ui-hero-text">Periksa lokasi, jadwal, jabatan, dan deskripsi pekerjaan. Catat keputusan sesuai prosedur keselamatan dan kesehatan kerja yang berlaku.</p><div class="ui-hero-actions"><a href="{{ route('admin.permohonan.index') }}" class="ui-button ui-button-light">Buka Antrean K3 <span aria-hidden="true">→</span></a></div></div>
            <div class="ui-hero-side" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M32 6 52 14v15c0 13-8 22-20 29C20 51 12 42 12 29V14L32 6Z"/><path d="m22 32 7 7 14-16"/></svg></div>
        </section>
        @include('dashboards.summary')
        <section class="ui-card">
            <h2 class="ui-card-title">Semua Pendaftar &amp; Riwayat Pendaftaran</h2>
            <p class="ui-card-text">Lihat seluruh pengajuan, termasuk yang masih ditinjau, disetujui, dan ditolak, beserta keputusan setiap bagian.</p>
            <a href="{{ route('admin.permohonan.history') }}" class="ui-card-link">Buka pendaftar &amp; riwayat →</a>
        </section>
        <section class="ui-grid ui-grid-2">
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 9 17H3L12 3Z"/><path d="M12 9v5m0 3h.01"/></svg></div><h3 class="ui-card-title">Antrean pemeriksaan K3</h3><p class="ui-card-text">Lihat permohonan yang menunggu keputusan bagian K3 dan periksa informasi pekerjaan sebelum mengambil tindakan.</p><a href="{{ route('admin.permohonan.index') }}" class="ui-card-link">Lihat antrean <span aria-hidden="true">→</span></a></article>
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 19 6v5c0 5-3 8-7 10-4-2-7-5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/></svg></div><h3 class="ui-card-title">Keputusan yang terdokumentasi</h3><p class="ui-card-text">Gunakan kolom catatan untuk menyimpan informasi pendukung atau alasan penolakan yang perlu diketahui pemohon.</p></article>
        </section>
    </div>
</x-app-layout>
