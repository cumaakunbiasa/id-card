<article class="ui-list-row">
<div class="ui-list-main">
<h3 class="ui-list-title">{{ $permit->job_title }}</h3>
<div class="ui-list-meta"><span><strong>PIC:</strong> {{ $permit->foreman_name ?: 'Belum tercatat' }}</span><span><strong>PT / Perusahaan:</strong> {{ $permit->company_name }}</span></div>
<p class="ui-section-note">{{ $permit->people_count }} orang termasuk PIC · {{ $permit->approved_people }} diizinkan · {{ $permit->pending_people }} ditinjau · {{ $permit->rejected_people }} ditolak</p>
@if(isset($permit->pending_for_role))<p class="ui-section-note">{{ $permit->pending_for_role }} orang menunggu keputusan bagian Anda.</p>@endif
</div>
<a class="ui-button ui-button-outline ui-button-sm" href="{{ route('permits.show', $permit->id) }}">Lihat detail</a>
</article>
