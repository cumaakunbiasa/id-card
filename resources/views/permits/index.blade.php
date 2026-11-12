<x-app-layout>
<x-slot name="header"><div class="ui-heading-row"><div><h1 class="ui-page-title">Pekerjaan Saya</h1><p class="ui-page-subtitle">Buka pekerjaan untuk melihat PIC, pekerja, dan status izin masing-masing.</p></div><a class="ui-button ui-button-primary" href="{{ route('permits.create') }}">Buat Permohonan</a></div></x-slot>
<div class="ui-page">
@if(session('success'))<div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>@endif
<section class="ui-card"><h2 class="ui-section-title">{{ $permits->total() }} kelompok pekerjaan</h2><p>{{ $counts->sum() }} orang termasuk PIC · {{ $counts->get('diizinkan', 0) }} diizinkan · {{ $counts->get('ditinjau', 0) }} ditinjau · {{ $counts->get('ditolak', 0) }} ditolak</p></section>
<section class="ui-panel">
@forelse($permits as $permit)
@include('permits.group-row')
@empty<div class="ui-empty">Belum ada pengajuan pekerjaan.</div>@endforelse
</section>
{{ $permits->links() }}
</div></x-app-layout>
