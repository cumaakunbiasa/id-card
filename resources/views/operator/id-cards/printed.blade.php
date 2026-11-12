<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div>
                <p class="ui-eyebrow">Area operator</p>
                <h1 class="ui-page-title">Kartu Sudah Dicetak</h1>
                <p class="ui-page-subtitle">Riwayat kartu pekerja yang sudah ditandai selesai dicetak.</p>
            </div>
            <a href="{{ route('operator.id-cards.index') }}" class="ui-button ui-button-outline">← Kembali ke kartu siap cetak</a>
        </div>
    </x-slot>

    <div class="ui-page">
        @if (session('success'))
            <div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>
        @endif

        <section class="ui-panel" style="padding:18px">
            <form method="GET" action="{{ route('operator.id-cards.printed') }}" class="ui-search-form">
                <label for="q" class="sr-only">Cari nama, perusahaan, jabatan, atau lokasi kerja</label>
                <input id="q" name="q" type="search" value="{{ $search }}" class="ui-input" placeholder="Cari nama, perusahaan, jabatan, atau lokasi kerja...">
                <button type="submit" class="ui-button ui-button-primary">Cari Kartu</button>
                @if ($search !== '')
                    <a href="{{ route('operator.id-cards.printed') }}" class="ui-button ui-button-outline">Reset</a>
                @endif
            </form>
        </section>

        <section class="ui-panel">
            <div class="ui-list-row" style="align-items:center;background:#fbfdff">
                <div>
                    <h2 class="ui-section-title">Riwayat pencetakan</h2>
                    <p class="ui-section-note">Kartu diurutkan berdasarkan waktu terakhir ditandai dicetak.</p>
                </div>
                <span class="ui-role-chip">{{ $permits->total() }} data</span>
            </div>

            @forelse ($permits as $permit)
                <article class="ui-list-row">
                    <div class="ui-list-main">
                        <div class="ui-list-title-row">
                            <h3 class="ui-list-title">{{ $permit->full_name }}</h3>
                            <span class="ui-status ui-status-diizinkan">Sudah dicetak</span>
                        </div>
                        <div class="ui-list-meta">
                            <span><strong>Perusahaan:</strong> {{ $permit->company_name }}</span>
                            <span><strong>Jabatan:</strong> {{ $permit->job_title }}</span>
                            <span><strong>Nomor:</strong> IK-{{ str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT) }}</span>
                            <span><strong>Lokasi kerja:</strong> {{ $permit->work_location }}</span>
                            <span><strong>Zona:</strong> @php
                        $displayZoneColor = $permit->id_card_header_color ?: $permit->zone_color;
                        $displayZone = collect(config('mitra.zones', []))->first(fn ($zone) => strtolower($zone['color'] ?? '') === strtolower($displayZoneColor ?? ''));
                    @endphp
                    {{ $displayZone['label'] ?? (config('mitra.zones.' . $permit->zone_key . '.label') ?? 'Belum dipilih') }}</span>
                            <span><strong>Waktu cetak:</strong> {{ $permit->printed_at->format('d/m/Y H:i') }}</span>
                            <span><strong>Periode:</strong> {{ $permit->work_starts_at?->format('d/m/Y') ?? $permit->start_date->format('d/m/Y') }} – {{ $permit->work_ends_at?->format('d/m/Y') ?? $permit->end_date->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div class="ui-list-action">
                        <a href="{{ route('operator.id-cards.show', $permit) }}" class="ui-button ui-button-primary ui-button-sm">Lihat Kartu <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @empty
                <div class="ui-empty">
                    <div class="ui-empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 8V3h10v5M6 18H4V9h16v9h-2"/><rect x="7" y="14" width="10" height="7" rx="1"/><path d="M17 12h.01"/></svg></div>
                    <h3 class="ui-empty-title">Belum ada kartu yang dicetak</h3>
                    <p class="ui-empty-text">
                        @if ($search !== '')
                            Tidak ada kartu tercetak yang cocok dengan pencarian “{{ $search }}”.
                        @else
                            Kartu akan muncul di sini setelah operator menandainya sudah dicetak.
                        @endif
                    </p>
                </div>
            @endforelse

            @if ($permits->hasPages())
                <div class="ui-list-footer">{{ $permits->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
