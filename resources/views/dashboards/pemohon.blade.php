<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div>
                <p class="ui-eyebrow">Portal pemohon</p>
                <h1 class="ui-page-title">Dashboard Pemohon</h1>
                <p class="ui-page-subtitle">Kelola pengajuan izin kerja dan pantau proses persetujuan dari satu tempat.</p>
            </div>
            <span class="ui-role-chip">Pemohon</span>
        </div>
    </x-slot>

    <div class="ui-page">
        @if (session('success'))
            <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="ui-hero">
            <div class="ui-hero-copy">
                <p class="ui-eyebrow">Sistem Pengajuan ID Card</p>
                <h2 class="ui-hero-title">Selamat datang, {{ auth()->user()->name }}.</h2>
                <p class="ui-hero-text">Mulai pengajuan baru, lengkapi informasi pekerjaan, lalu ikuti status pemeriksaan Humas, K3, dan Keamanan secara terpusat.</p>
                <div class="ui-hero-actions">
                    <a href="{{ route('permits.create') }}" class="ui-button ui-button-light">+ Buat Permohonan</a>
                    <a href="{{ route('permits.index') }}" class="ui-button" style="color:#fff;border-color:rgba(255,255,255,.35);background:rgba(255,255,255,.08)">Lihat Permohonan Saya <span aria-hidden="true">→</span></a>
                </div>
            </div>
            <div class="ui-hero-side" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="13" y="7" width="38" height="50" rx="6"/><path d="M23 20h18M23 29h18M23 38h8"/><path d="m35 42 4 4 8-9"/></svg>
            </div>
        </section>
        @include('dashboards.summary')

        <section>
            <div class="ui-section-head">
                <div><h2 class="ui-section-title">Akses cepat</h2><p class="ui-section-note">Pilih aktivitas yang ingin kamu lakukan.</p></div>
            </div>
            <div class="ui-grid ui-grid-3">
                <article class="ui-card">
                    <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg></div>
                    <h3 class="ui-card-title">Buat pengajuan baru</h3>
                    <p class="ui-card-text">Isi identitas, perusahaan, lokasi, deskripsi, dan periode pekerjaan dengan benar.</p>
                    <a href="{{ route('permits.create') }}" class="ui-card-link">Mulai pengajuan <span aria-hidden="true">→</span></a>
                </article>
                <article class="ui-card">
                    <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 4h8l3 3v13H5V4h3Z"/><path d="M8 11h8M8 15h8"/></svg></div>
                    <h3 class="ui-card-title">Pantau permohonan</h3>
                    <p class="ui-card-text">Lihat status pengajuan dan buka detail riwayat persetujuan setiap bagian.</p>
                    <a href="{{ route('permits.index') }}" class="ui-card-link">Lihat daftar <span aria-hidden="true">→</span></a>
                </article>
                <article class="ui-card">
                    <div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
                    <h3 class="ui-card-title">Pahami alur pemeriksaan</h3>
                    <p class="ui-card-text">Permohonan diperiksa oleh Humas, K3, dan Keamanan sebelum dinyatakan diizinkan.</p>
                </article>
            </div>
        </section>

        <section>
            <div class="ui-section-head"><div><h2 class="ui-section-title">Alur pengajuan</h2><p class="ui-section-note">Tiga tahap persetujuan sebelum izin diterbitkan.</p></div></div>
            <div class="ui-process-list">
                <div class="ui-process-step"><span class="ui-process-number">01</span><div><strong>Ajukan permohonan</strong><span>Lengkapi data pekerja dan informasi pekerjaan.</span></div></div>
                <div class="ui-process-step"><span class="ui-process-number">02</span><div><strong>Pemeriksaan bagian</strong><span>Humas, K3, dan Keamanan memberikan keputusan masing-masing.</span></div></div>
                <div class="ui-process-step"><span class="ui-process-number">03</span><div><strong>Status akhir</strong><span>Izin diberikan jika ketiga bagian menyetujui permohonan.</span></div></div>
            </div>
        </section>
    </div>
</x-app-layout>
