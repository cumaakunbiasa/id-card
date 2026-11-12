<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Panel penerbitan kartu</p><h1 class="ui-page-title">Dashboard Operator</h1><p class="ui-page-subtitle">Kelola pratinjau dan pencetakan ID Card untuk permohonan yang sudah diizinkan.</p></div>
            <span class="ui-role-chip">Operator ID Card</span>
        </div>
    </x-slot>
    <div class="ui-page">
        <section class="ui-hero">
            <div class="ui-hero-copy"><p class="ui-eyebrow">Area kerja operator</p><h2 class="ui-hero-title">Halo, {{ auth()->user()->name }}.</h2><p class="ui-hero-text">Gunakan daftar ID Card untuk menemukan permohonan yang telah disetujui Humas, K3, dan Keamanan. Periksa informasi kartu sebelum mencetak.</p>
          </div>
            </div>
            <div class="ui-hero-side" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="13" width="50" height="38" rx="6"/><circle cx="20" cy="27" r="5"/><path d="M12 42c1.5-5 4-7 8-7s6.5 2 8 7M34 25h14M34 33h14M34 41h8"/></svg></div>
        </section>
        @include('dashboards.summary')
        <section class="ui-grid ui-grid-3">
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M13 10h5M13 14h5"/></svg></div><h3 class="ui-card-title">Daftar kartu siap cetak</h3><p class="ui-card-text">Daftar hanya menampilkan permohonan berstatus diizinkan. Permohonan yang masih ditinjau atau ditolak tidak dapat dicetak.</p>
                @if (Route::has('operator.id-cards.index')) @endif
            </article>
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/><circle cx="12" cy="12" r="10"/></svg></div><h3 class="ui-card-title">Kartu sudah dicetak</h3><p class="ui-card-text">Buka riwayat kartu yang sudah ditandai selesai dicetak, termasuk waktu pencetakannya.</p>
                @if (Route::has('operator.id-cards.printed')) @endif
            </article>
            <article class="ui-card"><div class="ui-card-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 8V3h10v5M6 18H4V9h16v9h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/><path d="M17 12h.01"/></svg></div><h3 class="ui-card-title">Pratinjau sebelum cetak</h3><p class="ui-card-text">Pastikan nama, perusahaan, lokasi, dan masa berlaku sesuai dengan data permohonan sebelum mencetak kartu.</p></article>
        </section>
    </div>
</x-app-layout>
