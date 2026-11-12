<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Panel pemeriksaan</p><h1 class="ui-page-title">Dashboard Humas</h1><p class="ui-page-subtitle">Periksa permohonan dari sisi administrasi dan hubungan eksternal.</p></div>
            <span class="ui-role-chip">Bagian Humas</span>
        </div>
    </x-slot>
    <div class="ui-page">
        @if (session('success')) <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div> @endif
        <section class="ui-hero">
            <div class="ui-hero-copy"><p class="ui-eyebrow">Area kerja Humas</p><h2 class="ui-hero-title">Halo, {{ auth()->user()->name }}.</h2><p class="ui-hero-text">Tinjau data pemohon dan informasi perusahaan, lalu catat keputusan Humas dengan alasan yang jelas jika diperlukan.</p></div>
            <div class="ui-hero-side" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="14" y="8" width="36" height="48" rx="5"/><path d="M23 21h18M23 30h18M23 39h9"/><path d="m37 43 4 4 8-9"/></svg></div>
        </section>
        @include('dashboards.summary')
        <section class="ui-card">
            <h2 class="ui-card-title">Semua Pendaftar &amp; Riwayat Pendaftaran</h2>
            <p class="ui-card-text">Lihat seluruh pengajuan, termasuk yang masih ditinjau, disetujui, dan ditolak, beserta keputusan setiap bagian.</p>
            
        </section>
        <section class="ui-grid ui-grid-2">
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l3 3v13H5V4h3Z"/><path d="M8 11h8M8 15h5"/></svg></div><h3 class="ui-card-title">Antrean persetujuan</h3><p class="ui-card-text">Buka daftar permohonan yang menunggu keputusan Humas dan periksa detailnya sebelum memberi persetujuan.</p></article>
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div><h3 class="ui-card-title">Alur persetujuan</h3><p class="ui-card-text">Keputusan Humas merupakan satu dari tiga persetujuan. Permohonan hanya berstatus diizinkan setelah Humas, K3, dan Keamanan menyetujui.</p></article>
        </section>
    </div>
</x-app-layout>
