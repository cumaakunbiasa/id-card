<x-app-layout>
<x-slot name="header"><h1 class="ui-page-title">Pengajuan ID Card Mitra Kerja</h1><p class="ui-page-subtitle">Daftarkan pekerja sesuai kebutuhan dan satu PIC dengan lokasi kerja, pekerjaan, serta tanggal kerja yang sama.</p></x-slot>
<div class="ui-page">
@if ($errors->any())<div class="ui-alert ui-alert-error" role="alert"><strong>Periksa isian. Pilih ulang foto setelah validasi gagal.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('permits.store') }}" method="POST" enctype="multipart/form-data" class="ui-card ui-stack" x-data="{ count: {{ max(1, (int) old('worker_count', 1)) }}, names: @js(collect(old('workers', []))->pluck('name')->all()), submitting: false }" @submit="if (submitting) { $event.preventDefault(); return; } const bytes = Array.from($el.querySelectorAll('input[type=file]')).reduce((total, input) => total + (input.files[0]?.size || 0), 0); if (bytes > 100 * 1024 * 1024) { $event.preventDefault(); alert('Total foto maksimal 100 MB.'); return; } submitting = true">
@csrf
<input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">
<div class="ui-detail-grid">
<div><label class="ui-label" for="company">Perusahaan</label><input class="ui-input" id="company" name="company_name" value="{{ old('company_name') }}" maxlength="120" required></div>
<div><label class="ui-label" for="work-location">Lokasi kerja</label><input class="ui-input" id="work-location" type="text" name="work_location" value="{{ old('work_location') }}" maxlength="255" placeholder="Contoh: Parikesit" autocomplete="off" required><p class="ui-footer-note">Masukkan nama lokasi kerja sesuai area pekerjaan.</p></div>
<div><label class="ui-label" for="job">Pekerjaan para pekerja</label><input class="ui-input" id="job" name="job_title" value="{{ old('job_title') }}" maxlength="80" required></div>
<div><label class="ui-label" for="worker-count">Jumlah pekerja (di luar PIC)</label><input id="worker-count" class="ui-input" type="number" name="worker_count" min="1" step="1" required x-model.number="count" @change="$dispatch('mitra-count-changed')"></div>
<div><label class="ui-label" for="start">Mulai kerja (tanggal)</label><input class="ui-input" id="start" type="date" name="work_starts_at" value="{{ old('work_starts_at') }}" required></div>
<div><label class="ui-label" for="end">Selesai kerja (tanggal)</label><input class="ui-input" id="end" type="date" name="work_ends_at" value="{{ old('work_ends_at') }}" required></div>
<p class="ui-footer-note" style="grid-column:1/-1">Jadwal kerja dicatat berdasarkan tanggal, tanpa jam mulai atau selesai.</p>
</div>
<div><label class="ui-label" for="description">Uraian pekerjaan</label><textarea class="ui-textarea" id="description" name="work_description" maxlength="5000" required>{{ old('work_description') }}</textarea></div>
<section class="ui-card"><h2 class="ui-section-title">PIC</h2><label class="ui-label" for="foreman">Nama PIC</label><input class="ui-input" id="foreman" name="foreman_name" value="{{ old('foreman_name') }}" maxlength="80" required>
@include('permits.photo-input', ['field' => 'foreman_photo', 'inputId' => 'foreman-photo'])</section>
<template x-for="index in (Number.isSafeInteger(Number(count)) &amp;&amp; Number(count) > 0 ? Number(count) : 0)" :key="index">
<fieldset class="ui-card"><legend x-text="'Pekerja ' + index"></legend>
<label class="ui-label" :for="'worker-' + index">Nama lengkap</label>
<input class="ui-input" :id="'worker-' + index" :name="'workers[' + (index - 1) + '][name]'" :value="names[index - 1] || ''" @input="names[index - 1] = $event.target.value" maxlength="80" required>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:16px;align-items:start">
<div class="ui-stack" x-data="mitraPhoto" @mitra-count-changed.window="stop()" @pagehide.window="stop()">
<label class="ui-label" :for="'photo-' + index">Foto wajah</label>
<input :id="'photo-' + index" x-ref="photo" type="file" :name="'workers[' + (index - 1) + '][photo]'" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required @change="selected()">
<div class="ui-approval-actions"><button type="button" class="ui-button ui-button-outline" @click="$refs.photo.click()">Upload foto</button><button type="button" class="ui-button ui-button-outline" @click="openCamera()" :disabled="busy || camera">Buka kamera</button></div>
<p x-show="error" x-text="error" role="alert" class="ui-alert ui-alert-error" x-cloak></p>
<div x-show="camera" x-cloak><video x-ref="video" autoplay muted playsinline style="width:100%;max-width:320px;border-radius:10px" aria-label="Pratinjau kamera"></video><div class="ui-approval-actions"><button type="button" class="ui-button ui-button-primary" @click="capture()" :disabled="busy">Ambil foto</button><button type="button" class="ui-button ui-button-outline" @click="stop()">Tutup kamera</button></div></div>
<img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto" style="width:100px;height:130px;object-fit:cover;border-radius:10px">
</div>
<aside class="photo-requirements" style="text-align:left;font-weight:700">
        <h3 style="margin:0 0 7px;font-size:13px;line-height:1.5;font-weight:700;color:#1f2937;text-align:left;width:100%">* Ketentuan Foto</h3>
<ul style="margin:0;padding:0;list-style:none;display:grid;gap:4px;color:#374151;text-align:left;font-size:12px;line-height:1.6"><li>- Harus menggunakan helm dan wearpack/rompi.</li><li>- Melepas kacamata.</li></ul>
<p style="margin:12px 0 0;color:#4b5563;font-size:13px;line-height:1.6;text-align:left">Setiap foto yang tidak sesuai dengan persyaratan dan ketentuan yang berlaku tidak akan mendapatkan persetujuan (approval) dari User Approval.</p>
</aside>
</div>

</div></fieldset></template>
<p>Foto hanya JPG/JPEG/PNG. Total foto maksimal 100 MB per pengajuan. Buka kamera untuk mengambil foto langsung, atau upload dari perangkat. Izinkan akses kamera saat diminta; gunakan localhost atau HTTPS.</p>
<button class="ui-button ui-button-primary" type="submit" :disabled="submitting" x-text="submitting ? 'Mengirim…' : 'Kirim Pengajuan ID Card'">Kirim Pengajuan ID Card</button>
</form></div></x-app-layout>
