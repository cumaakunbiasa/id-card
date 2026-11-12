# Panduan deployment — kandidat rilis

## Status

Source telah disiapkan untuk proses build dan deployment Laravel. Belum dipublikasikan dan **belum dinyatakan siap produksi**: pengujian PHP/Laravel, browser, SMTP, dan server tujuan masih harus lulus. Hosting belum dipilih.

Pemeriksaan yang sudah dilakukan di lingkungan penyusunan:

- Kompilasi CSS menggunakan Tailwind CLI berhasil.
- Pemeriksaan sintaks shell `scripts/check-release.sh` berhasil.
- Skrip pemeriksaan berhenti dengan benar saat PHP tidak tersedia.
- PHP/Composer tidak tersedia; download dependensi juga terhalang jaringan. Tes Laravel/PHP lint belum berjalan.
- Build Vite penuh belum lulus akibat native binding Rolldown yang tidak tersedia; kompilasi CSS bukan pengganti build Vite.
- Belum ada pengujian browser atau deployment nyata.

## Isi paket

Paket source bersih: tidak memuat `.env` aktif, database pengguna, `vendor`, `node_modules`, log, cache runtime, atau hasil build lama. Database dari revisi sebelumnya **tidak dimigrasikan atau dihapus** oleh pembuatan paket ini. Pertahankan backup database instalasi Anda.

Tambahan:

- Dashboard berdasarkan data dan hak akses nyata.
- Layout login/register yang konsisten, ringkasan dashboard, navigasi mobile, fokus keyboard, skip link, dan dukungan reduced motion.
- Halaman error 403, 404, 419, 429, 500, 503 yang tidak memerlukan hasil build aset.
- Header keamanan dan `no-store` untuk halaman pengguna yang telah masuk.
- Perintah `php artisan staff:create` untuk membuat akun internal tanpa password default.
- `.env.production.example`, template Nginx, dan skrip pemeriksaan rilis.

## 1. Siapkan staging / mesin build

Gunakan PHP >=8.3 sesuai lockfile, Composer 2, PDO driver database tujuan, dan Node.js yang memenuhi engine Vite (`^20.19.0 || >=22.12.0`). Pastikan ekstensi PHP memenuhi `composer check-platform-reqs`.

```bash
composer install --prefer-dist --no-interaction
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
bash scripts/check-release.sh
```

Perintah `cp` di Windows PowerShell diganti `Copy-Item .env.example .env`. Skrip Bash dijalankan dengan Git Bash/WSL atau jalankan perintah di dalam skrip secara manual. Jangan jalankan tes di folder produksi aktif.

Tes memakai SQLite in-memory dari `phpunit.xml`; ekstensi PDO SQLite diperlukan meskipun produksi menggunakan MySQL. `check-release.sh` menolak cached config untuk menghindari penggunaan konfigurasi database yang salah.

Pastikan `APP_KEY` staging tersedia, jalankan seluruh tes, dan tinjau kegagalannya sebelum meneruskan. Build yang berhasil menghasilkan `public/build/manifest.json`. Jangan unggah source CSS/JS tanpa membangun aset terlebih dahulu.

## 2. Siapkan produksi

1. Pilih domain dan hosting yang mendukung PHP, database persisten, dan direktori `public` Laravel sebagai document root.
2. Buat database kosong dan akun database khusus aplikasi, atau gunakan database aktif yang sudah dicadangkan untuk upgrade.
3. Salin `.env.production.example` menjadi `.env`, lalu isi domain, database, dan SMTP dengan nilai nyata. Jangan commit atau bagikan file `.env`.
4. Instal dependensi dan build aset pada mesin build atau server yang mendukung Node:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
composer check-platform-reqs --no-dev
npm ci
npm run build
```

5. Untuk **instalasi baru saja**, buat APP_KEY:

```bash
php artisan key:generate --force
```

Untuk upgrade, **pertahankan APP_KEY lama** agar data terenkripsi dan sesi yang terkait tidak rusak.

6. Pastikan user PHP-FPM dapat menulis `storage` dan `bootstrap/cache`; gunakan kepemilikan/grup yang benar, bukan `chmod 777`. User web tidak perlu menulis seluruh source.
7. Setelah backup dan sebelum membuka trafik:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

8. Buat akun petugas yang diperlukan:

```bash
php artisan staff:create
```

Perintah meminta nama, email, bagian, dan password secara interaktif. Email yang sudah ada ditolak; perintah ini tidak menimpa akun lama. Jangan jalankan seeder demo di produksi.

## 3. Web server dan HTTPS

- Document root wajib menuju `public/`, bukan folder proyek utama.
- `deploy/nginx.conf.example` adalah template **HTTP di belakang reverse proxy TLS tepercaya**, bukan konfigurasi HTTPS lengkap. Sesuaikan domain, root, dan socket PHP-FPM. Jangan mengekspos listener HTTP tersebut langsung tanpa konfigurasi HTTPS yang sesuai.
- Untuk Nginx yang langsung menerima trafik publik, pasang sertifikat TLS dan listener HTTPS; alihkan HTTP ke HTTPS. Validasi dengan `nginx -t` sebelum reload.
- Jika memakai reverse proxy, konfigurasikan trusted proxies Laravel hanya untuk alamat proxy yang benar agar skema HTTPS, URL dan cookie sesuai. Jangan mempercayai semua proxy tanpa memahami jaringan server.
- cPanel/shared hosting: arahkan document root domain ke folder `public`. Jika hosting melarangnya, minta konfigurasi Laravel dari penyedia hosting; jangan memindahkan seluruh proyek ke `public_html`.
- Template menggunakan `SESSION_SECURE_COOKIE=true`; akses publik wajib HTTPS agar login berfungsi.
- `APP_DEBUG=false` wajib untuk produksi. Jangan tampilkan exception detail kepada pengguna.

## 4. SMTP dan pemulihan akun

Fitur lupa password bergantung pada pengiriman email. Isi host, port, kredensial, dan alamat pengirim berdasarkan penyedia email. Sesuaikan `MAIL_SCHEME` jika penyedia membutuhkannya. Lakukan pengujian email nyata pada staging sebelum go-live.

Model User belum menerapkan kewajiban verifikasi email. Akun baru dapat login tanpa verifikasi email. Kebijakan ini dipertahankan; jangan menyatakan email pengguna sudah terverifikasi.

## 5. Pemeriksaan sebelum go-live

Jalankan di staging dengan database uji:

- Register pemohon, login, logout, dan reset password melalui email nyata.
- Pengajuan baru membuat tepat tiga baris keputusan.
- Humas/K3/Keamanan melihat seluruh riwayat tetapi hanya memutuskan bagiannya sendiri.
- Penolakan tanpa alasan ditolak; tiga persetujuan mengubah status menjadi diizinkan; keputusan final tidak bisa diulang.
- Pemohon tidak dapat membaca pengajuan orang lain.
- Operator hanya melihat pengajuan diizinkan dan dapat membuka pratinjau cetak.
- Ringkasan dashboard sesuai data, filter dan pagination berfungsi.
- Uji layar 360 px, 768 px, dan desktop, menu mobile, tab keyboard, error form, dan pencetakan kartu.
- Pastikan `/up` memberi HTTP 200, halaman login bisa dibuka, sertifikat valid, serta file `.env` dan database tidak dapat diunduh.
- Jalankan pengujian persetujuan bersamaan pada engine database produksi; tes SQLite tidak membuktikan perilaku locking MySQL/PostgreSQL.

## 6. Operasional dan rollback

- Pantau `/up` untuk kesehatan proses aplikasi. Endpoint ini bukan bukti semua layanan, SMTP, dan database berfungsi; tambahkan smoke test aplikasi sesuai infrastruktur.
- Cadangkan database secara terjadwal ke lokasi terpisah dan uji proses restore. Simpan APP_KEY di secret manager/backup aman.
- Atur rotasi log dan pemantauan error tanpa menaruh password atau data sensitif dalam log.
- Aplikasi saat ini tidak mewajibkan queue worker untuk alur izin. Jika menambahkan notifikasi queued, jalankan worker yang dikelola process supervisor.
- Simpan source/build versi sebelumnya. Saat gagal setelah deploy, aktifkan maintenance jika diperlukan, pulihkan source/build sebelumnya, pertahankan `.env` dan data persisten, lalu bangun ulang cache konfigurasi.
- Revisi ini tidak menambah migration. Untuk revisi skema mendatang, jangan otomatis melakukan `migrate:rollback` tanpa analisis dampak data; gunakan backup dan rencana migrasi yang teruji.

## Batasan yang harus disadari

- Izin belum otomatis kedaluwarsa berdasarkan tanggal akhir; operator mengikuti status `diizinkan`.
- Penghapusan akun bersejarah dicegah di endpoint profil, tetapi SQL langsung masih dapat melewati aturan tersebut.
- Riwayat keputusan bukan audit log menyeluruh.
- CSP ketat belum diterapkan karena Alpine dan beberapa halaman masih menggunakan inline script/style; jangan mengklaim CSP sudah aktif.
- Tidak ada deployment atau pengiriman email sungguhan yang dilakukan dalam penyusunan paket ini.


## Pembaruan upload dan email pengajuan

Lihat `PERBAIKAN-23479.md` untuk migrasi token, batas total foto 100 MB, konfigurasi PHP/Nginx, dan worker khusus `permit_mail`. Konfigurasi ini diperlukan sebelum menggunakan fitur baru.


## Gmail SMTP langsung

Gunakan `GMAIL-SMTP.md` sebagai panduan email pengajuan. Pengirim idmitrakerja@gmail.com, App Password diisi pada server, worker koneksi permit_mail wajib aktif.
