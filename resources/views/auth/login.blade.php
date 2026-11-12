<x-guest-layout>
    <p class="ui-eyebrow">Selamat datang kembali</p>
    <h2 class="ui-page-title">Masuk ke akun Anda</h2>
    <p class="ui-page-subtitle" style="margin-bottom:28px">Lanjutkan pengajuan dan pemeriksaan izin kerja.</p>
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <form method="POST" action="{{ route('login') }}" class="ui-stack" x-data="{ showPassword: false }">
        @csrf
        <div><label for="email" class="ui-label">Email</label><input id="email" class="ui-input" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" aria-describedby="email-error" @if($errors->has('email')) aria-invalid="true" @endif><div id="email-error"><x-input-error :messages="$errors->get('email')" class="mt-2" /></div></div>
        <div>
            <label for="password" class="ui-label">Kata sandi</label>
            <div style="position:relative">
                <input id="password" class="ui-input" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" aria-describedby="password-error" style="padding-right:88px">
                <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" :aria-pressed="showPassword.toString()" x-text="showPassword ? 'Sembunyikan' : 'Lihat'" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:0;background:transparent;color:#1267b1;font-size:12px;font-weight:700;cursor:pointer">
                    Lihat
                </button>
            </div>
            <div id="password-error"><x-input-error :messages="$errors->get('password')" class="mt-2" /></div>
        </div>
        <div class="auth-options"><label><input id="remember_me" type="checkbox" name="remember"> Ingat saya</label><a href="{{ route('password.request') }}">Lupa kata sandi?</a></div>
        <button type="submit" class="ui-button ui-button-primary">Masuk →</button>
    </form>
    <p class="auth-register">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai pemohon</a></p>
</x-guest-layout>
