<x-app-layout>
<x-slot name="header"><h1 class="ui-page-title">Notifikasi</h1></x-slot>
<div class="ui-page">
@forelse($notifications as $notification)
<article class="ui-card">
<h2>{{ $notification->data['title'] ?? 'Pemberitahuan' }}</h2>
<p>{{ $notification->data['message'] ?? '' }}</p>
<p>{{ $notification->created_at->format('d/m/Y H:i') }} · {{ $notification->read_at ? 'Sudah dibaca' : 'Belum dibaca' }}</p>
@if(($notification->data['event'] ?? '') === 'permit_submitted')
<a href="{{ route('admin.permohonan.index') }}">Lihat pengajuan</a>
@elseif(($notification->data['event'] ?? '') === 'permit_decision')
<a href="{{ route('permits.show', $notification->data['permit_id']) }}">Lihat keputusan</a>
@endif
@if(!$notification->read_at)
<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="ui-button" type="submit">Tandai sudah dibaca</button></form>
@endif
</article>
@empty<p>Belum ada notifikasi.</p>@endforelse
{{ $notifications->links() }}
</div></x-app-layout>
