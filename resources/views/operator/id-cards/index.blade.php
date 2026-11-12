<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Area operator</p><h1 class="ui-page-title">Pilih Pekerja untuk ID Card Mitra Kerja</h1><p class="ui-page-subtitle">Daftar ini hanya menampilkan kartu yang sudah diizinkan dan belum ditandai dicetak.</p></div>
            <a href="{{ route('dashboard') }}" class="ui-button ui-button-outline">← Dashboard</a>
        </div>
    </x-slot>

    <div class="ui-page">
        @if (session('success')) <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div> @endif
        <section class="ui-hero">
            <div class="ui-hero-copy"><p class="ui-eyebrow">Penerbitan ID Card</p><h2 class="ui-hero-title">Kartu siap dicetak setelah tiga persetujuan.</h2><p class="ui-hero-text">Daftar ini hanya menampilkan permohonan berstatus diizinkan setelah Humas, K3, dan Keamanan menyetujui.</p></div>
            <div class="ui-hero-side" aria-hidden="true"><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="13" width="50" height="38" rx="6"/><circle cx="20" cy="27" r="5"/><path d="M12 42c1.5-5 4-7 8-7s6.5 2 8 7M34 25h14M34 33h14M34 41h8"/></svg></div>
        </section>

        <section class="ui-panel" style="padding:18px">
            <form method="GET" action="{{ route('operator.id-cards.index') }}" class="ui-search-form">
                <label for="q" class="sr-only">Cari nama, perusahaan, jabatan, atau lokasi kerja</label>
                <input id="q" name="q" type="search" value="{{ $search }}" class="ui-input" placeholder="Cari nama, perusahaan, jabatan, atau lokasi kerja...">
                <button type="submit" class="ui-button ui-button-primary">Cari Permohonan</button>
                @if ($search !== '') <a href="{{ route('operator.id-cards.index') }}" class="ui-button ui-button-outline">Reset</a> @endif
                <a href="{{ route('operator.id-cards.printed') }}" class="ui-button ui-button-outline">Lihat Kartu Sudah Dicetak</a>
            </form>
        </section>

        <section class="ui-panel">
            <div class="ui-list-row" style="align-items:center;background:#fbfdff">
                <div><h2 class="ui-section-title">Kartu siap dicetak</h2><p class="ui-section-note">Pilih “Pilih & Pratinjau” untuk memeriksa dan mencetak kartu.</p></div>
                <span class="ui-role-chip">{{ $permits->total() }} data</span>
            </div>
            @forelse ($permits as $permit)
                <article class="ui-list-row">
                    <div class="ui-list-main">
                        <div class="ui-list-title-row"><h3 class="ui-list-title">{{ $permit->full_name }}</h3><span class="ui-status ui-status-diizinkan">Diizinkan</span></div>
                        <div class="ui-list-meta"><span><strong>Perusahaan:</strong> {{ $permit->company_name }}</span><span><strong>Jabatan:</strong> {{ $permit->job_title }}</span><span><strong>Nomor:</strong> IK-{{ str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT) }}</span><span><strong>Lokasi kerja:</strong> {{ $permit->work_location }}</span><span><strong>Zona:</strong> @php
                        $displayZoneColor = $permit->id_card_header_color ?: $permit->zone_color;
                        $displayZone = collect(config('mitra.zones', []))->first(fn ($zone) => strtolower($zone['color'] ?? '') === strtolower($displayZoneColor ?? ''));
                    @endphp
                    {{ $displayZone['label'] ?? (config('mitra.zones.' . $permit->zone_key . '.label') ?? 'Belum dipilih') }}</span><span><strong>Periode:</strong> {{ $permit->work_starts_at?->format('d/m/Y') ?? $permit->start_date->format('d/m/Y') }} – {{ $permit->work_ends_at?->format('d/m/Y') ?? $permit->end_date->format('d/m/Y') }}</span></div>
                    </div>
                    <div class="ui-list-action"><a href="{{ route('operator.id-cards.show', $permit) }}" class="ui-button ui-button-primary ui-button-sm">Pilih & Pratinjau <span aria-hidden="true">→</span></a></div>
                </article>
            @empty
                <div class="ui-empty">
                    <div class="ui-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="11" r="2"/><path d="M13 10h5M13 14h5"/></svg></div>
                    <h3 class="ui-empty-title">Belum ada ID Card siap cetak</h3>
                    <p class="ui-empty-text">@if ($search !== '')Tidak ada permohonan diizinkan yang cocok dengan pencarian “{{ $search }}”.@else Permohonan akan muncul di sini setelah Humas, K3, dan Keamanan menyetujuinya.@endif</p>
                </div>
            @endforelse
            @if ($permits->hasPages()) <div class="ui-list-footer">{{ $permits->links() }}</div> @endif
        </section>
    </div>
</x-app-layout>
