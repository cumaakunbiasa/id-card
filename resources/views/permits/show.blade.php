<x-app-layout>
<x-slot name="header"><div class="ui-heading-row"><div><h1 class="ui-page-title">{{ $jobTitle }}</h1><p class="ui-page-subtitle">PIC: {{ $pic ?: 'Belum tercatat' }} · {{ $workPermit->company_name }}</p></div><a class="ui-button ui-button-outline" href="{{ auth()->user()->role === 'pemohon' ? route('permits.index') : route('admin.permohonan.index') }}">Kembali ke daftar pekerjaan</a></div></x-slot>
@php
$statuses = ['ditinjau' => 'Sedang ditinjau', 'diizinkan' => 'Diizinkan', 'ditolak' => 'Ditolak'];
$decisions = ['menunggu' => 'Menunggu keputusan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
$roles = ['humas' => 'Humas', 'k3' => 'K3', 'keamanan' => 'Keamanan'];
@endphp
<div class="ui-page">
@if(session('success'))<div class="ui-alert ui-alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="ui-alert ui-alert-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="ui-card"><h2 class="ui-section-title">Detail pekerjaan</h2>
<div class="ui-detail-grid">
<div><strong>Pekerjaan:</strong> {{ $jobTitle }}</div><div><strong>PT / Perusahaan:</strong> {{ $workPermit->company_name }}</div>
<div><strong>PIC:</strong> {{ $pic ?: 'Belum tercatat' }}</div><div><strong>Lokasi kerja:</strong> {{ $workPermit->work_location }}</div>
<div><strong>Mulai kerja:</strong> {{ $workPermit->work_starts_at?->format('d/m/Y') ?? $workPermit->start_date->format('d/m/Y') }}</div><div><strong>Selesai kerja:</strong> {{ $workPermit->work_ends_at?->format('d/m/Y') ?? $workPermit->end_date->format('d/m/Y') }}</div>
</div><p><strong>Uraian pekerjaan:</strong> {{ $workPermit->work_description }}</p>
<p>{{ $memberCounts->sum() }} orang termasuk PIC · {{ $memberCounts->get('diizinkan', 0) }} diizinkan · {{ $memberCounts->get('ditinjau', 0) }} ditinjau · {{ $memberCounts->get('ditolak', 0) }} ditolak</p>
</section>
<h2 class="ui-section-title">PIC dan pekerja</h2>
<p>Setiap orang memerlukan persetujuan Humas, K3, dan Keamanan. Penolakan satu orang tidak membatalkan izin anggota lain.</p>
@foreach($members as $member)
<article class="ui-card" id="person-{{ $member->id }}">
<div class="ui-heading-row"><div><h3 class="ui-section-title">{{ $member->full_name }}</h3><p>{{ $member->person_type === 'mandor' ? 'PIC' : 'Pekerja' }} · IK-{{ str_pad((string) $member->id, 6, '0', STR_PAD_LEFT) }}</p></div><span class="ui-status ui-status-{{ $member->status }}">{{ $statuses[$member->status] ?? $member->status }}</span></div>
@if($member->photo_path)
<div style="margin:12px 0">
<strong style="display:block;margin-bottom:8px">Foto pekerja</strong>
<img src="{{ route('permits.photo', $member) }}" alt="Foto {{ $member->full_name }}" loading="lazy" style="display:block;width:150px;height:190px;object-fit:cover;border-radius:10px;border:1px solid #dbe3ec">
</div>
@else
<p class="ui-footer-note">Foto belum tersedia.</p>
@endif
<div class="ui-grid ui-grid-3">
@foreach($roles as $role => $label)
@php $approval = $member->approvals->firstWhere('admin_role', $role); @endphp
<div class="ui-approval-card"><strong>{{ $label }}</strong><p>{{ $decisions[$approval?->status] ?? 'Belum tersedia' }}</p><p>Petugas: {{ $approval?->admin?->name ?? '-' }}</p><p>{{ $approval?->decided_at?->format('d/m/Y H:i') ?? '-' }}</p>@if($approval?->notes)<p class="ui-note-box">{{ $approval->notes }}</p>@endif</div>
@endforeach
</div>
@php $ownApproval = $member->approvals->firstWhere('admin_role', auth()->user()->role); @endphp
@if(isset($roles[auth()->user()->role]) && $member->status === 'ditinjau' && $ownApproval?->status === 'menunggu')
<form method="POST" action="{{ route('admin.permohonan.decide', [$member, 'setuju']) }}" class="ui-approval-form">
@csrf
<input type="hidden" name="_permit_id" value="{{ $member->id }}">
<label class="ui-label" for="notes-{{ $member->id }}">Catatan untuk {{ $member->full_name }} (wajib jika ditolak)</label>
<textarea class="ui-textarea" id="notes-{{ $member->id }}" name="notes" maxlength="2000">{{ (string) old('_permit_id') === (string) $member->id ? old('notes') : '' }}</textarea>
<div class="ui-approval-actions">
<button class="ui-button ui-button-success" type="submit">Izinkan orang ini</button>
<button class="ui-button ui-button-danger" type="submit" formaction="{{ route('admin.permohonan.decide', [$member, 'tolak']) }}">Tolak orang ini</button>
</div></form>
@endif
</article>
@endforeach
{{ $members->links() }}
</div></x-app-layout>
