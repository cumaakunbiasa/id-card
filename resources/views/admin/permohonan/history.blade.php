<x-app-layout>
    <x-slot name="header">
        <div class="ui-heading-row">
            <div><p class="ui-eyebrow">Pendaftaran izin kerja</p><h1 class="ui-page-title">Semua Pendaftar &amp; Riwayat Pendaftaran</h1><p class="ui-page-subtitle">Seluruh pengajuan dan riwayat keputusan Humas, K3, serta Keamanan.</p></div>
            <a href="{{ route('admin.permohonan.index') }}" class="ui-button ui-button-outline">Antrean Persetujuan</a>
        </div>
    </x-slot>
    @php
        $statuses = ['ditinjau' => 'Sedang Ditinjau', 'diizinkan' => 'Disetujui / Diizinkan', 'ditolak' => 'Ditolak'];
        $decisions = ['menunggu' => 'Menunggu Keputusan', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak'];
        $roles = ['humas' => 'Humas', 'k3' => 'K3', 'keamanan' => 'Keamanan'];
    @endphp
    <div class="ui-page">
        <a href="{{ route('admin.workers.export') }}" class="ui-button ui-button-primary">Export semua data pekerja ke Excel</a>
        @if ($errors->any())
            <div class="ui-alert ui-alert-error" role="alert">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
        @endif
        <section class="ui-card">
            <form method="GET" action="{{ route('admin.permohonan.history') }}">
                <div style="margin-bottom:16px">
                    <label class="ui-label" for="history-search">Cari nama, perusahaan, atau nomor izin</label>
                    <input id="history-search" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" class="ui-input" placeholder="Contoh: IK-000001">
                </div>
                <div class="ui-detail-grid">
                    <div><label class="ui-label" for="history-from">Didaftarkan mulai tanggal</label><input id="history-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}" class="ui-input"></div>
                    <div><label class="ui-label" for="history-to">Didaftarkan sampai tanggal</label><input id="history-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}" class="ui-input"></div>
                    <div>
                        <label class="ui-label" for="history-status">Status pendaftaran</label>
                        <select name="status" id="history-status" class="ui-input">
                            <option value="">Semua status</option>
                            @foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ui-label" for="history-decision">Keputusan bagian {{ $roles[auth()->user()->role] }}</label>
                        <select name="decision" id="history-decision" class="ui-input">
                            <option value="">Semua keputusan</option>
                            @foreach ($decisions as $value => $label)<option value="{{ $value }}" @selected(($filters['decision'] ?? '') === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                </div>
                <p class="ui-section-note">Status disetujui/diizinkan berarti ketiga bagian sudah menyetujui. Filter keputusan bagian menampilkan keputusan bagian kamu, termasuk pengajuan yang masih diproses bagian lain.</p>
                <div class="ui-approval-actions">
                    <button type="submit" class="ui-button ui-button-primary">Terapkan Filter</button>
                    <a href="{{ route('admin.permohonan.history') }}" class="ui-button ui-button-outline">Reset</a>
                </div>
            </form>
        </section>
        <section class="ui-panel">
            <div class="ui-list-row"><h2 class="ui-section-title">Riwayat Pendaftaran</h2><span class="ui-role-chip">{{ $permits->total() }} pendaftaran</span></div>
            @forelse ($permits as $permit)
                <article class="ui-list-row" style="display:block">
                    <div class="ui-heading-row">
                        <div>
                            <div class="ui-list-title-row"><h3 class="ui-list-title">{{ $permit->full_name }}</h3><span class="ui-status ui-status-{{ $permit->status }}">{{ $statuses[$permit->status] ?? $permit->status }}</span></div>
                            <div class="ui-list-meta"><span>IK-{{ str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT) }}</span><span>{{ $permit->company_name }}</span><span>Didaftarkan: {{ $permit->created_at?->format('d M Y, H:i') ?? '-' }}</span><span>Akun pemohon: {{ $permit->user?->name ?? '-' }}</span></div>
                        </div>
                        <a href="{{ route('permits.show', $permit) }}" class="ui-button ui-button-outline ui-button-sm">Detail &amp; Riwayat</a>
                    </div>
                    <div class="ui-grid ui-grid-3" style="margin-top:16px">
                        @foreach ($roles as $role => $label)
                            @php $approval = $permit->approvals->firstWhere('admin_role', $role); @endphp
                            <div class="ui-approval-card">
                                <div class="ui-approval-top"><strong>{{ $label }}</strong><span class="ui-status ui-status-{{ $approval?->status ?? 'default' }}">{{ $decisions[$approval?->status] ?? 'Belum tersedia' }}</span></div>
                                <div class="ui-approval-meta"><div>Petugas: {{ $approval?->admin?->name ?? '-' }}</div><div>Waktu: {{ $approval?->decided_at?->format('d M Y, H:i') ?? '-' }}</div></div>
                                @if ($approval?->notes)<p class="ui-note-box">{{ $approval->notes }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @empty
                <div class="ui-empty"><h3 class="ui-empty-title">Tidak ada pendaftaran</h3><p class="ui-empty-text">Belum ada pendaftaran yang sesuai dengan filter yang dipilih.</p></div>
            @endforelse
        </section>
        {{ $permits->links() }}
    </div>
</x-app-layout>
