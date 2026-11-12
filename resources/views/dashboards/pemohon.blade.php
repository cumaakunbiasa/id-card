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

    <div class="ui-page ui-page-pemohon">
        @if (session('success'))
            <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="ui-hero">
            <div class="ui-hero-copy">
                <p class="ui-eyebrow">Sistem Pengajuan ID Card</p>
                <h2 class="ui-hero-title">Selamat datang, {{ auth()->user()->name }}.</h2>
                <p class="ui-hero-text">Mulai pengajuan baru, lengkapi informasi pekerjaan, lalu ikuti status pemeriksaan Humas, K3, dan Keamanan secara terpusat.</p>
            </div>
            <div class="ui-hero-side" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="13" y="7" width="38" height="50" rx="6"/><path d="M23 20h18M23 29h18M23 38h8"/><path d="m35 42 4 4 8-9"/></svg>
            </div>
        </section>

        @include('dashboards.summary')

        <section class="ui-card ui-process-card">
            <div class="ui-section-head">
                <div>
                    <h2 class="ui-section-title">Alur pengajuan</h2>
                    <p class="ui-section-note">Tiga tahap persetujuan sebelum izin diterbitkan.</p>
                </div>
            </div>
            <div class="ui-process-list">
                <div class="ui-process-step">
                    <span class="ui-process-number">01</span>
                    <div><strong>Ajukan permohonan</strong><span>Lengkapi data pekerja dan informasi pekerjaan.</span></div>
                </div>
                <div class="ui-process-step">
                    <span class="ui-process-number">02</span>
                    <div><strong>Pemeriksaan bagian</strong><span>Humas, K3, dan Keamanan memberikan keputusan masing-masing.</span></div>
                </div>
                <div class="ui-process-step">
                    <span class="ui-process-number">03</span>
                    <div><strong>Status akhir</strong><span>Izin diberikan jika ketiga bagian menyetujui permohonan.</span></div>
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
