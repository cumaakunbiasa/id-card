<x-app-layout>
<x-slot name="header"><h1 class="ui-page-title">Antrean Persetujuan Pekerjaan</h1><p class="ui-page-subtitle">Bagian {{ strtoupper(auth()->user()->role) }} · Buka detail pekerjaan untuk memberikan keputusan per orang.</p></x-slot>
<div class="ui-page">
@if(session('success'))<div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>@endif
<a class="ui-button ui-button-outline" href="{{ route('admin.permohonan.history') }}">Riwayat keputusan per orang</a>
<section class="ui-panel"><div class="ui-list-row"><h2 class="ui-section-title">{{ $permits->total() }} pekerjaan menunggu pemeriksaan</h2></div>
@forelse($permits as $permit)
@include('permits.group-row')
@empty<div class="ui-empty">Tidak ada pekerjaan yang menunggu keputusan bagian Anda.</div>@endforelse
</section>
{{ $permits->links() }}
<p>Penolakan hanya berlaku untuk orang yang dipilih. Anggota kelompok lainnya tetap diperiksa secara terpisah.</p>
</div></x-app-layout>
