@php
    $labels = ['ditinjau' => 'Sedang ditinjau', 'diizinkan' => 'Diizinkan', 'ditolak' => 'Ditolak'];
@endphp
<section class="ui-grid ui-grid-4" aria-label="Ringkasan pendaftaran">
    <article class="ui-stat"><div class="ui-stat-top">{{ auth()->user()->role === 'operator' ? 'Izin disetujui' : 'Total pendaftaran' }}</div><p class="ui-stat-value">{{ number_format($counts->sum(), 0, ',', '.') }}</p><span class="ui-stat-note">{{ auth()->user()->role === 'pemohon' ? 'Pengajuan milik Anda' : 'Sesuai akses akun Anda' }}</span></article>
    @foreach ($labels as $status => $label)
        <article class="ui-stat"><div class="ui-stat-top">{{ $label }}</div><p class="ui-stat-value">{{ number_format($counts->get($status, 0), 0, ',', '.') }}</p><span class="ui-status ui-status-{{ $status }}">{{ $label }}</span></article>
    @endforeach
</section>
@if ($pending !== null)
    <div class="ui-task-banner"><div><strong>{{ $pending }} pengajuan menunggu bagian Anda</strong><p>Periksa data dan catat keputusan untuk melanjutkan proses izin.</p></div><a class="ui-button ui-button-primary" href="{{ route('admin.permohonan.index') }}">Periksa antrean →</a></div>
@endif
<section class="ui-panel">
    <div class="ui-list-row"><div><h2 class="ui-section-title">Pendaftaran terbaru</h2><p class="ui-section-note">Lima pengajuan terbaru sesuai akses akun Anda.</p></div></div>
    @forelse ($recentPermits as $permit)
        <div class="ui-list-row">
            <div class="ui-list-main"><h3 class="ui-list-title">{{ $permit->full_name }}</h3><div class="ui-list-meta"><span>{{ $permit->company_name }}</span><span>IK-{{ str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT) }}</span><span>{{ $permit->created_at?->format('d M Y, H:i') }}</span></div></div>
            <div class="ui-list-action"><span class="ui-status ui-status-{{ $permit->status }}">{{ $labels[$permit->status] ?? $permit->status }}</span><a class="ui-button ui-button-outline ui-button-sm" href="{{ auth()->user()->role === 'operator' ? route('operator.id-cards.show', $permit) : route('permits.show', $permit) }}">Lihat detail</a></div>
        </div>
    @empty
        <div class="ui-empty"><h3 class="ui-empty-title">Belum ada pendaftaran</h3><p class="ui-empty-text">Data akan muncul di sini setelah pengajuan tersedia untuk akun Anda.</p></div>
    @endforelse
</section>
