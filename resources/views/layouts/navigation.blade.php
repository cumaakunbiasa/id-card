@php
    $roleLabels = [
        'pemohon' => 'Pemohon',
        'humas' => 'Bagian Humas',
        'k3' => 'Bagian K3',
        'keamanan' => 'Bagian Keamanan',
        'operator' => 'Operator ID Card',
    ];
    $user = auth()->user();
@endphp

<nav x-data="{ open: false }" class="app-nav" aria-label="Navigasi utama">
    <div class="app-nav-inner">
        <a class="app-brand" href="{{ route('dashboard') }}" aria-label="PLN Indonesia Power, dashboard">
            <span class="app-brand-mark">
                <img src="{{ asset(config('mitra.logo')) }}" alt="Logo PLN Indonesia Power">
            </span>
        </a>

        <div class="app-nav-links">
            <a href="{{ route('dashboard') }}" class="app-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
            @if ($user->role === 'pemohon')
                <a href="{{ route('permits.index') }}" class="app-nav-link {{ request()->routeIs('permits.index', 'permits.show') ? 'is-active' : '' }}">Permohonan Saya</a>
                <a href="{{ route('permits.create') }}" class="app-nav-link {{ request()->routeIs('permits.create') ? 'is-active' : '' }}">Buat Permohonan</a>
            @endif
            @if (in_array($user->role, ['humas', 'k3', 'keamanan'], true))
                <a href="{{ route('admin.permohonan.index') }}" class="app-nav-link {{ request()->routeIs('admin.permohonan.index') ? 'is-active' : '' }}">Antrean Persetujuan</a>
                <a href="{{ route('admin.permohonan.history') }}" class="app-nav-link {{ request()->routeIs('admin.permohonan.history') ? 'is-active' : '' }}">Pendaftar &amp; Riwayat</a>
            @endif
            @if ($user->role === 'operator' && Route::has('operator.id-cards.index'))
                <a href="{{ route('operator.id-cards.index') }}" class="app-nav-link {{ request()->routeIs('operator.id-cards.index') ? 'is-active' : '' }}">Kartu Siap Dicetak</a>
                <a href="{{ route('operator.id-cards.printed') }}" class="app-nav-link {{ request()->routeIs('operator.id-cards.printed') ? 'is-active' : '' }}">Kartu Sudah Dicetak</a>
            @endif
        </div>

        <div class="app-user-area">
            <span class="app-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            <span class="app-user-copy">
                <strong>{{ $user->name }}</strong>
                <small>{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</small>
            </span>
            <div class="app-user-actions">
                <a class="app-mini-link" href="{{ route('notifications.index') }}">Notifikasi ({{ $user->unreadNotifications()->count() }})</a>
                <a class="app-mini-link" href="{{ route('profile.edit') }}">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="app-logout">Keluar</button>
                </form>
            </div>
        </div>

        <button type="button" class="app-mobile-toggle" @click="open = !open" :aria-expanded="open.toString()" aria-label="Buka menu navigasi">
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
            <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="m6 6 12 12M18 6 6 18" /></svg>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition class="app-mobile-menu">
        <a href="{{ route('dashboard') }}" class="app-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
        @if ($user->role === 'pemohon')
            <a href="{{ route('permits.index') }}" class="app-nav-link {{ request()->routeIs('permits.index', 'permits.show') ? 'is-active' : '' }}">Permohonan Saya</a>
            <a href="{{ route('permits.create') }}" class="app-nav-link {{ request()->routeIs('permits.create') ? 'is-active' : '' }}">Buat Permohonan</a>
        @endif
        @if (in_array($user->role, ['humas', 'k3', 'keamanan'], true))
            <a href="{{ route('admin.permohonan.index') }}" class="app-nav-link {{ request()->routeIs('admin.permohonan.index') ? 'is-active' : '' }}">Antrean Persetujuan</a>
                <a href="{{ route('admin.permohonan.history') }}" class="app-nav-link {{ request()->routeIs('admin.permohonan.history') ? 'is-active' : '' }}">Pendaftar &amp; Riwayat</a>
        @endif
        @if ($user->role === 'operator' && Route::has('operator.id-cards.index'))
            <a href="{{ route('operator.id-cards.index') }}" class="app-nav-link {{ request()->routeIs('operator.id-cards.index') ? 'is-active' : '' }}">Kartu Siap Dicetak</a>
            <a href="{{ route('operator.id-cards.printed') }}" class="app-nav-link {{ request()->routeIs('operator.id-cards.printed') ? 'is-active' : '' }}">Kartu Sudah Dicetak</a>
        @endif
        <div class="app-mobile-user">
            <strong>{{ $user->name }}</strong><br>
            {{ $user->email }} · {{ $roleLabels[$user->role] ?? ucfirst($user->role) }}
            <div class="mt-3 flex gap-2">
                <a class="app-mini-link" href="{{ route('notifications.index') }}">Notifikasi ({{ $user->unreadNotifications()->count() }})</a>
                <a class="app-mini-link" href="{{ route('profile.edit') }}">Profil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="app-logout">Keluar</button>
                </form>
            </div>
        </div>
    </div>
</nav>
