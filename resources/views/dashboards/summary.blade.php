@if(auth()->user()->role === 'operator')
    @include('dashboards.operator-summary')
@else
<section class="ui-grid ui-grid-4" aria-label="Ringkasan pekerjaan dan izin per orang">
<article class="ui-stat"><div class="ui-stat-top">Kelompok pekerjaan</div><p class="ui-stat-value">{{ $groupCount }}</p><span class="ui-stat-note">{{ $counts->sum() }} orang termasuk PIC</span></article>
@foreach(['ditinjau' => 'Orang ditinjau', 'diizinkan' => 'Orang diizinkan', 'ditolak' => 'Orang ditolak'] as $status => $label)
<article class="ui-stat"><div class="ui-stat-top">{{ $label }}</div><p class="ui-stat-value">{{ $counts->get($status, 0) }}</p></article>
@endforeach
</section>
@if($pending !== null)
<div class="ui-task-banner"><div><strong>{{ $pending }} orang menunggu keputusan bagian Anda</strong><p>Buka kelompok pekerjaan untuk memeriksa PIC dan pekerja satu per satu.</p></div><a class="ui-button ui-button-primary" href="{{ route('admin.permohonan.index') }}">Periksa antrean</a></div>
@endif
<section class="ui-panel"><div class="ui-list-row"><h2 class="ui-section-title">Pekerjaan terbaru</h2></div>
@forelse($recentPermits as $permit)
    @include('permits.group-row')
@empty
<div class="ui-empty">Belum ada pengajuan pekerjaan.</div>
@endforelse
</section>
@endif
