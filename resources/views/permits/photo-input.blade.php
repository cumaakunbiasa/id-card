<div class="photo-upload-layout" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:16px;align-items:start">
    <div class="ui-stack" x-data="mitraPhoto" @mitra-count-changed.window="stop()" @pagehide.window="stop()">
        <label class="ui-label" for="{{ $inputId }}">Foto wajah</label>
        <input id="{{ $inputId }}" x-ref="photo" type="file" name="{{ $field }}" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required @change="selected()">
        <div class="ui-approval-actions">
            <button type="button" class="ui-button ui-button-outline" @click="$refs.photo.click()">Upload foto</button>
            <button type="button" class="ui-button ui-button-outline" @click="openCamera()" :disabled="busy || camera">Buka kamera</button>
        </div>
        <p x-show="error" x-text="error" role="alert" class="ui-alert ui-alert-error" x-cloak></p>
        <div x-show="camera" x-cloak>
            <video x-ref="video" autoplay muted playsinline style="width:100%;max-width:320px;border-radius:10px" aria-label="Pratinjau kamera"></video>
            <div class="ui-approval-actions">
                <button type="button" class="ui-button ui-button-primary" @click="capture()" :disabled="busy">Ambil foto</button>
                <button type="button" class="ui-button ui-button-outline" @click="stop()">Tutup kamera</button>
            </div>
        </div>
        <img x-show="preview" x-cloak :src="preview" alt="Pratinjau foto yang dipilih" style="width:100px;height:130px;object-fit:cover;border-radius:10px">
    </div>
    <aside class="photo-requirements" style="text-align:left;font-weight:700">
        <h3 style="margin:0 0 7px;font-size:13px;line-height:1.5;font-weight:700;color:#1f2937;text-align:left;width:100%">* Ketentuan Foto</h3>
        <ul style="margin:0;padding:0;list-style:none;display:grid;gap:4px;color:#374151;text-align:left;font-size:12px;line-height:1.6">
            <li>- Harus menggunakan helm dan wearpack/rompi.</li>
            <li>- Melepas kacamata.</li>
        </ul>
        <p style="margin:12px 0 0;color:#4b5563;font-size:13px;line-height:1.6;text-align:left">
            Setiap foto yang tidak sesuai dengan persyaratan dan ketentuan yang berlaku tidak akan mendapatkan persetujuan (approval) dari User Approval.
        </p>
    </aside>
</div>